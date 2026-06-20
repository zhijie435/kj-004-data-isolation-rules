<?php

namespace Database\Seeders;

use App\Models\DataIsolationRule;
use Illuminate\Database\Seeder;

class DataIsolationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => '课程租户隔离',
                'code' => 'COURSE_TENANT_ISOLATION',
                'type' => 'tenant',
                'model' => 'App\\Models\\Course',
                'scope' => 'tenant',
                'role' => null,
                'field' => 'tenant_id',
                'operator' => '=',
                'value' => '{{tenant_id}}',
                'condition_expression' => null,
                'params' => null,
                'field_mapping' => null,
                'is_active' => true,
                'priority' => 1,
                'description' => '确保用户只能访问本租户的课程数据',
            ],
            [
                'name' => '教师课程隔离',
                'code' => 'TEACHER_COURSE_ISOLATION',
                'type' => 'role',
                'model' => 'App\\Models\\Course',
                'scope' => 'role',
                'role' => 'teacher',
                'field' => 'teacher_id',
                'operator' => '=',
                'value' => '{{id}}',
                'condition_expression' => null,
                'params' => null,
                'field_mapping' => null,
                'is_active' => true,
                'priority' => 10,
                'description' => '教师只能看到自己教授的课程',
            ],
            [
                'name' => '学生已报名课程隔离',
                'code' => 'STUDENT_ENROLLED_COURSE',
                'type' => 'custom',
                'model' => 'App\\Models\\Course',
                'scope' => 'user',
                'role' => 'student',
                'field' => null,
                'operator' => null,
                'value' => null,
                'condition_expression' => 'id IN (SELECT c.course_id FROM classes c INNER JOIN class_student cs ON c.id = cs.class_id WHERE cs.student_id = {user_id} AND cs.status = ?)',
                'params' => ['status' => 'enrolled'],
                'field_mapping' => null,
                'is_active' => true,
                'priority' => 20,
                'description' => '学生只能看到自己已报名的课程',
            ],
            [
                'name' => '班级租户隔离',
                'code' => 'CLASS_TENANT_ISOLATION',
                'type' => 'tenant',
                'model' => 'App\\Models\\ClassModel',
                'scope' => 'tenant',
                'role' => null,
                'field' => 'tenant_id',
                'operator' => '=',
                'value' => '{{tenant_id}}',
                'condition_expression' => null,
                'params' => null,
                'field_mapping' => null,
                'is_active' => true,
                'priority' => 1,
                'description' => '确保用户只能访问本租户的班级数据',
            ],
            [
                'name' => '用户租户隔离',
                'code' => 'USER_TENANT_ISOLATION',
                'type' => 'tenant',
                'model' => 'App\\Models\\User',
                'scope' => 'tenant',
                'role' => null,
                'field' => 'tenant_id',
                'operator' => '=',
                'value' => '{{tenant_id}}',
                'condition_expression' => null,
                'params' => null,
                'field_mapping' => null,
                'is_active' => true,
                'priority' => 1,
                'description' => '确保用户只能访问本租户的用户数据',
            ],
            [
                'name' => '仅显示启用课程',
                'code' => 'ACTIVE_COURSES_ONLY',
                'type' => 'field',
                'model' => 'App\\Models\\Course',
                'scope' => 'global',
                'role' => null,
                'field' => 'status',
                'operator' => '=',
                'value' => '1',
                'condition_expression' => null,
                'params' => null,
                'field_mapping' => null,
                'is_active' => false,
                'priority' => 50,
                'description' => '全局过滤，只显示状态为启用的课程（默认禁用）',
            ],
        ];

        foreach ($rules as $rule) {
            DataIsolationRule::firstOrCreate(['code' => $rule['code']], $rule);
        }
    }
}
