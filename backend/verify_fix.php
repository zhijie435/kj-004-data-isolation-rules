<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Hashing\HashManager;
use App\Models\Tenant;
use App\Models\School;
use App\Models\User;
use App\Models\Course;
use App\Models\ClassModel;
use App\Models\DataIsolationRule;
use App\Services\DataIsolationService;

$container = new Container;

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'sqlite',
    'database'  => ':memory:',
    'prefix'    => '',
]);
$capsule->setEventDispatcher(new Dispatcher($container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

$config = new ConfigRepository([
    'hashing' => [
        'driver' => 'bcrypt',
        'bcrypt' => ['rounds' => 10],
    ],
]);
$container->instance('config', $config);
$container->instance('db', $capsule->getDatabaseManager());
$container->singleton('hash', function ($app) {
    return new HashManager($app);
});
$container->singleton('hash.driver', function ($app) {
    return $app['hash']->driver();
});

$container->singleton('auth', function () {
    return new class {
        public function user() {
            return null;
        }
        public function check() {
            return false;
        }
        public function guest() {
            return true;
        }
    };
});

Facade::setFacadeApplication($container);

Capsule::schema()->create('tenants', function ($table) {
    $table->id();
    $table->string('name');
    $table->string('code')->unique();
    $table->boolean('status')->default(true);
    $table->timestamps();
});

Capsule::schema()->create('schools', function ($table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    $table->string('name');
    $table->string('code')->unique();
    $table->boolean('status')->default(true);
    $table->timestamps();
});

Capsule::schema()->create('users', function ($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
    $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('set null');
    $table->string('type')->default('student')->comment('用户类型: admin, teacher, student');
    $table->index('type');
    $table->rememberToken();
    $table->timestamps();
});

Capsule::schema()->create('data_isolation_rules', function ($table) {
    $table->id();
    $table->string('name');
    $table->string('code')->unique();
    $table->string('type')->default('tenant');
    $table->string('model');
    $table->string('scope')->nullable();
    $table->string('role')->nullable();
    $table->string('field')->nullable();
    $table->string('operator')->nullable();
    $table->string('value')->nullable();
    $table->text('condition_expression')->nullable();
    $table->json('params')->nullable();
    $table->json('field_mapping')->nullable();
    $table->boolean('is_active')->default(true);
    $table->integer('priority')->default(0);
    $table->text('description')->nullable();
    $table->timestamps();
});

Capsule::schema()->create('courses', function ($table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('set null');
    $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
    $table->string('name', 200);
    $table->text('description')->nullable();
    $table->boolean('status')->default(true);
    $table->timestamps();
    $table->index(['tenant_id', 'status']);
});

Capsule::schema()->create('classes', function ($table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('set null');
    $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null');
    $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
    $table->foreignId('head_teacher_id')->nullable()->constrained('users')->onDelete('set null');
    $table->string('name', 200);
    $table->string('grade', 50)->nullable();
    $table->boolean('status')->default(true);
    $table->timestamps();
    $table->index(['tenant_id', 'status']);
});

Capsule::schema()->create('class_student', function ($table) {
    $table->id();
    $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
    $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
    $table->string('status')->default('enrolled')->comment('报名状态: enrolled, dropped, pending');
    $table->timestamp('enrolled_at')->nullable();
    $table->timestamps();
    $table->index('status');
    $table->unique(['class_id', 'student_id']);
});

echo "=== 数据表创建成功 ===\n\n";

$passed = 0;
$failed = 0;

function assert_test($name, $condition, $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo "✅ PASS: $name\n";
        $passed++;
    } else {
        echo "❌ FAIL: $name";
        if ($detail) echo " - $detail";
        echo "\n";
        $failed++;
    }
}

$tenant1 = Tenant::create(['name' => 'Tenant A', 'code' => 'tenant-a']);
$tenant2 = Tenant::create(['name' => 'Tenant B', 'code' => 'tenant-b']);

$superAdmin = User::create([
    'name' => 'Super Admin',
    'email' => 'super@example.com',
    'password' => 'password',
    'type' => 'admin',
    'tenant_id' => null,
]);

$tenant1Admin = User::create([
    'name' => 'Tenant1 Admin',
    'email' => 'admin1@example.com',
    'password' => 'password',
    'type' => 'admin',
    'tenant_id' => $tenant1->id,
]);

$teacher1 = User::create([
    'name' => 'Teacher 1',
    'email' => 'teacher1@example.com',
    'password' => 'password',
    'type' => 'teacher',
    'tenant_id' => $tenant1->id,
]);

$teacher2 = User::create([
    'name' => 'Teacher 2',
    'email' => 'teacher2@example.com',
    'password' => 'password',
    'type' => 'teacher',
    'tenant_id' => $tenant2->id,
]);

$student1 = User::create([
    'name' => 'Student 1',
    'email' => 'student1@example.com',
    'password' => 'password',
    'type' => 'student',
    'tenant_id' => $tenant1->id,
]);

$student2 = User::create([
    'name' => 'Student 2',
    'email' => 'student2@example.com',
    'password' => 'password',
    'type' => 'student',
    'tenant_id' => $tenant1->id,
]);

$course1 = Course::create([
    'tenant_id' => $tenant1->id,
    'teacher_id' => $teacher1->id,
    'name' => 'Course 1 (T1, Teacher1)',
    'description' => 'Course for Tenant 1, taught by Teacher 1',
]);

$course2 = Course::create([
    'tenant_id' => $tenant1->id,
    'teacher_id' => $teacher1->id,
    'name' => 'Course 2 (T1, Teacher1)',
    'description' => 'Another course for Tenant 1, taught by Teacher 1',
]);

$course3 = Course::create([
    'tenant_id' => $tenant2->id,
    'teacher_id' => $teacher2->id,
    'name' => 'Course 3 (T2, Teacher2)',
    'description' => 'Course for Tenant 2, taught by Teacher 2',
]);

$class1 = ClassModel::create([
    'tenant_id' => $tenant1->id,
    'course_id' => $course1->id,
    'teacher_id' => $teacher1->id,
    'head_teacher_id' => $teacher1->id,
    'name' => 'Class 1A',
    'grade' => 'Grade 1',
]);

$class2 = ClassModel::create([
    'tenant_id' => $tenant1->id,
    'course_id' => $course2->id,
    'teacher_id' => $teacher1->id,
    'name' => 'Class 2A',
    'grade' => 'Grade 2',
]);

$class1->students()->attach($student1->id, ['status' => 'enrolled', 'enrolled_at' => now()]);
$class1->students()->attach($student2->id, ['status' => 'dropped', 'enrolled_at' => now()]);

echo "=== 测试数据创建成功 ===\n\n";
echo "测试数据概览：\n";
echo "- 超级管理员: Super Admin (无租户)\n";
echo "- 租户1管理员: Tenant1 Admin\n";
echo "- 租户1教师: Teacher 1 (教授 Course 1 & 2)\n";
echo "- 租户2教师: Teacher 2 (教授 Course 3)\n";
echo "- 租户1学生: Student 1 (已报名 Class 1->Course 1)、Student 2 (已退课 Class 1)\n";
echo "- Course 1: Tenant1, Teacher1 → Class 1A (Student1 enrolled, Student2 dropped)\n";
echo "- Course 2: Tenant1, Teacher1 → Class 2A (无学生)\n";
echo "- Course 3: Tenant2, Teacher2\n\n";

echo "=== 开始运行测试 ===\n\n";

$service = new DataIsolationService();

echo "--- Test 1: 超级管理员绕过隔离 ---\n";
$service->setUser($superAdmin);
assert_test('超级管理员 shouldBypassForCurrentUser() 返回 true', $service->shouldBypassForCurrentUser() === true);
$query1 = Course::withoutGlobalScopes();
$service->applyIsolationRules($query1, new Course());
$result1 = $query1->count();
assert_test('超级管理员可查看全部 3 门课程', $result1 === 3, "实际返回: $result1");

echo "\n--- Test 2: 租户管理员不能绕过隔离 ---\n";
$service->setUser($tenant1Admin);
assert_test('租户管理员 shouldBypassForCurrentUser() 返回 false', $service->shouldBypassForCurrentUser() === false);

echo "\n--- Test 3: 租户隔离 ---\n";
$query3 = Course::withoutGlobalScopes();
$service->applyIsolationRules($query3, new Course());
$result3 = $query3->count();
$courseNames3 = $query3->pluck('name')->toArray();
assert_test('租户管理员只能看本租户的 2 门课程', $result3 === 2, "实际返回: $result3, 课程: " . implode(', ', $courseNames3));
assert_test('租户管理员看不到 Tenant2 的 Course 3', !in_array('Course 3 (T2, Teacher2)', $courseNames3));

echo "\n--- Test 4: 教师只能看自己教授的课程 ---\n";
$service->setUser($teacher1);
$query4 = Course::withoutGlobalScopes();
$service->applyIsolationRules($query4, new Course());
$result4 = $query4->count();
$courseNames4 = $query4->pluck('name')->toArray();
assert_test('Teacher1 只能看自己教授的 2 门课程', $result4 === 2, "实际返回: $result4, 课程: " . implode(', ', $courseNames4));

$service->setUser($teacher2);
$query4b = Course::withoutGlobalScopes();
$service->applyIsolationRules($query4b, new Course());
$result4b = $query4b->count();
$courseNames4b = $query4b->pluck('name')->toArray();
assert_test('Teacher2 只能看自己教授的 1 门课程 (Course3)', $result4b === 1, "实际返回: $result4b, 课程: " . implode(', ', $courseNames4b));
assert_test('Teacher2 看到的是 Course 3', in_array('Course 3 (T2, Teacher2)', $courseNames4b));

echo "\n--- Test 5: 学生只能看已报名课程（核心修复验证） ---\n";
$service->setUser($student1);
$query5 = Course::withoutGlobalScopes();
$service->applyIsolationRules($query5, new Course());
$result5 = $query5->count();
$courseNames5 = $query5->pluck('name')->toArray();
assert_test('Student1 已报名 Class1(Course1)，应该能看到 1 门课程', $result5 === 1, "实际返回: $result5, 课程: " . implode(', ', $courseNames5));
assert_test('Student1 看到的课程是 Course 1', in_array('Course 1 (T1, Teacher1)', $courseNames5), "实际: " . implode(', ', $courseNames5));
assert_test('Student1 看不到 Course 2 (未报名)', !in_array('Course 2 (T1, Teacher1)', $courseNames5));

$service->setUser($student2);
$query5b = Course::withoutGlobalScopes();
$service->applyIsolationRules($query5b, new Course());
$result5b = $query5b->count();
$courseNames5b = $query5b->pluck('name')->toArray();
assert_test('Student2 已退课(dropped)，应该看不到任何课程', $result5b === 0, "实际返回: $result5b, 课程: " . implode(', ', $courseNames5b));

echo "\n--- Test 6: checkDataAccess 方法 - 不同租户 ---\n";
$service->setUser($tenant1Admin);
assert_test('租户1管理员不能访问租户2的 Course 3', $service->checkDataAccess($course3) === false);

echo "\n--- Test 7: checkDataAccess 方法 - 相同租户 ---\n";
assert_test('租户1管理员可以访问租户1的 Course 1', $service->checkDataAccess($course1) === true);

echo "\n--- Test 8: getUserDataScopeSummary 方法 ---\n";
$service->setUser($superAdmin);
$summary8a = $service->getUserDataScopeSummary();
assert_test('超级管理员 scope = global', $summary8a['scope'] === 'global', json_encode($summary8a, JSON_UNESCAPED_UNICODE));

$service->setUser($teacher1);
$summary8b = $service->getUserDataScopeSummary();
assert_test('教师 scope = teacher', $summary8b['scope'] === 'teacher', json_encode($summary8b, JSON_UNESCAPED_UNICODE));

$service->setUser($student1);
$summary8c = $service->getUserDataScopeSummary();
assert_test('学生 scope = student', $summary8c['scope'] === 'student', json_encode($summary8c, JSON_UNESCAPED_UNICODE));

$service->setUser($tenant1Admin);
$summary8d = $service->getUserDataScopeSummary();
assert_test('租户管理员 scope = tenant', $summary8d['scope'] === 'tenant', json_encode($summary8d, JSON_UNESCAPED_UNICODE));

echo "\n=== 测试结果汇总 ===\n";
echo "通过: $passed\n";
echo "失败: $failed\n";
echo "总计: " . ($passed + $failed) . "\n";

if ($failed > 0) {
    echo "\n⚠️  有测试失败，请检查修复！\n";
    exit(1);
} else {
    echo "\n🎉 所有测试通过！数据隔离规则修复成功！\n";
    exit(0);
}
