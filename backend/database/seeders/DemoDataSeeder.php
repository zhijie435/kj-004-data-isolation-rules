<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\School;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tsinghua = Tenant::where('code', 'Tsinghua')->first();
        $pku = Tenant::where('code', 'PKU')->first();
        $fudan = Tenant::where('code', 'Fudan')->first();

        $csSchool = School::where('code', 'TH-CS')->first();
        if (!$csSchool) {
            $csSchool = School::create([
                'name' => '清华大学计算机学院',
                'code' => 'TH-CS',
                'tenant_id' => $tsinghua->id,
                'status' => true,
            ]);
        }

        $eeSchool = School::firstOrCreate(
            ['tenant_id' => $tsinghua->id, 'code' => 'TH-EE'],
            [
                'name' => '清华大学电子工程学院',
                'code' => 'TH-EE',
                'tenant_id' => $tsinghua->id,
                'status' => true,
            ]
        );

        $pkuGuanghua = School::firstOrCreate(
            ['tenant_id' => $pku->id, 'code' => 'PKU-GSM'],
            [
                'name' => '北京大学光华管理学院',
                'code' => 'PKU-GSM',
                'tenant_id' => $pku->id,
                'status' => true,
            ]
        );

        $teacher1 = User::where('email', 'th_teacher1@example.com')->first();
        $teacher2 = User::where('email', 'th_teacher2@example.com')->first();
        $student1 = User::where('email', 'th_student1@example.com')->first();
        $student2 = User::where('email', 'th_student2@example.com')->first();

        $course1 = Course::firstOrCreate(
            ['tenant_id' => $tsinghua->id, 'name' => '数据结构与算法'],
            [
                'tenant_id' => $tsinghua->id,
                'school_id' => $csSchool->id,
                'teacher_id' => $teacher1->id,
                'name' => '数据结构与算法',
                'description' => '本课程系统介绍常用数据结构和算法设计与分析方法',
                'status' => true,
            ]
        );

        $course2 = Course::firstOrCreate(
            ['tenant_id' => $tsinghua->id, 'name' => '计算机网络原理'],
            [
                'tenant_id' => $tsinghua->id,
                'school_id' => $csSchool->id,
                'teacher_id' => $teacher1->id,
                'name' => '计算机网络原理',
                'description' => '介绍计算机网络的基本原理、协议和架构',
                'status' => true,
            ]
        );

        $course3 = Course::firstOrCreate(
            ['tenant_id' => $tsinghua->id, 'name' => '操作系统'],
            [
                'tenant_id' => $tsinghua->id,
                'school_id' => $csSchool->id,
                'teacher_id' => $teacher2->id,
                'name' => '操作系统',
                'description' => '操作系统的基本概念、原理和实现技术',
                'status' => true,
            ]
        );

        $course4 = Course::firstOrCreate(
            ['tenant_id' => $tsinghua->id, 'name' => '信号处理基础'],
            [
                'tenant_id' => $tsinghua->id,
                'school_id' => $eeSchool->id,
                'teacher_id' => $teacher2->id,
                'name' => '信号处理基础',
                'description' => '数字信号处理的基本理论和方法',
                'status' => true,
            ]
        );

        $course5 = Course::firstOrCreate(
            ['tenant_id' => $pku->id, 'name' => '微观经济学'],
            [
                'tenant_id' => $pku->id,
                'school_id' => $pkuGuanghua->id,
                'teacher_id' => null,
                'name' => '微观经济学',
                'description' => '研究个体经济单位的经济行为',
                'status' => true,
            ]
        );

        $class1 = ClassModel::firstOrCreate(
            ['tenant_id' => $tsinghua->id, 'name' => '计科2021级1班'],
            [
                'tenant_id' => $tsinghua->id,
                'school_id' => $csSchool->id,
                'course_id' => $course1->id,
                'teacher_id' => $teacher1->id,
                'name' => '计科2021级1班',
                'grade' => 'Grade 1',
                'status' => true,
            ]
        );

        $class2 = ClassModel::firstOrCreate(
            ['tenant_id' => $tsinghua->id, 'name' => '计科2021级2班'],
            [
                'tenant_id' => $tsinghua->id,
                'school_id' => $csSchool->id,
                'course_id' => $course2->id,
                'teacher_id' => $teacher1->id,
                'name' => '计科2021级2班',
                'grade' => 'Grade 1',
                'status' => true,
            ]
        );

        $class3 = ClassModel::firstOrCreate(
            ['tenant_id' => $tsinghua->id, 'name' => '软工2021级1班'],
            [
                'tenant_id' => $tsinghua->id,
                'school_id' => $csSchool->id,
                'course_id' => $course3->id,
                'teacher_id' => $teacher2->id,
                'name' => '软工2021级1班',
                'grade' => 'Grade 1',
                'status' => true,
            ]
        );

        $existingEnrollment1 = DB::table('class_student')
            ->where('class_id', $class1->id)
            ->where('student_id', $student1->id)
            ->exists();
        if (!$existingEnrollment1) {
            $student1->classes()->attach($class1->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);
        }

        $existingEnrollment2 = DB::table('class_student')
            ->where('class_id', $class2->id)
            ->where('student_id', $student1->id)
            ->exists();
        if (!$existingEnrollment2) {
            $student1->classes()->attach($class2->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);
        }

        $existingEnrollment3 = DB::table('class_student')
            ->where('class_id', $class2->id)
            ->where('student_id', $student2->id)
            ->exists();
        if (!$existingEnrollment3) {
            $student2->classes()->attach($class2->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);
        }
    }
}
