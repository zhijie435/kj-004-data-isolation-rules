<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DataIsolationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataIsolationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DataIsolationService $isolationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->isolationService = app(DataIsolationService::class);
    }

    public function test_super_admin_can_bypass_isolation()
    {
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'type' => 'admin',
        ]);

        $this->isolationService->setUser($superAdmin);

        $this->assertTrue($this->isolationService->shouldBypassForCurrentUser());
    }

    public function test_tenant_admin_cannot_bypass_isolation()
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'admin',
        ]);

        $this->isolationService->setUser($admin);

        $this->assertFalse($this->isolationService->shouldBypassForCurrentUser());
    }

    public function test_tenant_isolation_applied_correctly()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $course1 = Course::factory()->create(['tenant_id' => $tenant1->id]);
        $course2 = Course::factory()->create(['tenant_id' => $tenant2->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant1->id,
            'type' => 'student',
        ]);

        $this->isolationService->setUser($user);

        $query = Course::query();
        $this->isolationService->applyIsolationRules($query, new Course());

        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($course1->id, $results->first()->id);
    }

    public function test_teacher_can_only_see_own_courses()
    {
        $tenant = Tenant::factory()->create();

        $teacher1 = User::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'teacher',
        ]);

        $teacher2 = User::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'teacher',
        ]);

        $course1 = Course::factory()->create([
            'tenant_id' => $tenant->id,
            'teacher_id' => $teacher1->id,
        ]);

        $course2 = Course::factory()->create([
            'tenant_id' => $tenant->id,
            'teacher_id' => $teacher2->id,
        ]);

        $this->isolationService->setUser($teacher1);

        $query = Course::query();
        $this->isolationService->applyIsolationRules($query, new Course());

        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($course1->id, $results->first()->id);
    }

    public function test_student_can_only_see_enrolled_courses()
    {
        $tenant = Tenant::factory()->create();
        $teacher = User::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'teacher',
        ]);

        $course1 = Course::factory()->create([
            'tenant_id' => $tenant->id,
            'teacher_id' => $teacher->id,
        ]);

        $course2 = Course::factory()->create([
            'tenant_id' => $tenant->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = User::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'student',
        ]);

        $class = \App\Models\ClassModel::factory()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course1->id,
            'head_teacher_id' => $teacher->id,
        ]);

        $student->classes()->attach($class->id, [
            'enrolled_at' => now(),
            'status' => 'enrolled',
        ]);

        $this->isolationService->setUser($student);

        $query = Course::query();
        $this->isolationService->applyIsolationRules($query, new Course());

        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($course1->id, $results->first()->id);
    }

    public function test_check_data_access_returns_false_for_different_tenant()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant1->id,
            'type' => 'student',
        ]);

        $course = Course::factory()->create(['tenant_id' => $tenant2->id]);

        $this->isolationService->setUser($user);

        $this->assertFalse($this->isolationService->checkDataAccess($course));
    }

    public function test_check_data_access_returns_true_for_same_tenant()
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'student',
        ]);

        $course = Course::factory()->create(['tenant_id' => $tenant->id]);

        $this->isolationService->setUser($user);

        $this->assertTrue($this->isolationService->checkDataAccess($course));
    }

    public function test_get_user_data_scope_summary_returns_correct_scope()
    {
        $tenant = Tenant::factory()->create();

        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'type' => 'admin',
        ]);

        $teacher = User::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'teacher',
        ]);

        $student = User::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'student',
        ]);

        $this->isolationService->setUser($superAdmin);
        $this->assertEquals('global', $this->isolationService->getUserDataScopeSummary()['scope']);

        $this->isolationService->setUser($teacher);
        $this->assertEquals('teacher', $this->isolationService->getUserDataScopeSummary()['scope']);

        $this->isolationService->setUser($student);
        $this->assertEquals('student', $this->isolationService->getUserDataScopeSummary()['scope']);
    }
}
