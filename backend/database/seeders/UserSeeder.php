<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => '超级管理员',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password123'),
                'type' => 'admin',
                'tenant_id' => null,
                'school_id' => null,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super_admin');

        $tsinghua = Tenant::where('code', 'Tsinghua')->first();
        $pku = Tenant::where('code', 'PKU')->first();

        $tsinghuaAdmin = User::firstOrCreate(
            ['email' => 'th_admin@example.com'],
            [
                'name' => '清华管理员',
                'email' => 'th_admin@example.com',
                'password' => Hash::make('password123'),
                'type' => 'admin',
                'tenant_id' => $tsinghua->id,
                'school_id' => null,
                'email_verified_at' => now(),
            ]
        );
        $tsinghuaAdmin->assignRole('tenant_admin');

        $pkuAdmin = User::firstOrCreate(
            ['email' => 'pku_admin@example.com'],
            [
                'name' => '北大管理员',
                'email' => 'pku_admin@example.com',
                'password' => Hash::make('password123'),
                'type' => 'admin',
                'tenant_id' => $pku->id,
                'school_id' => null,
                'email_verified_at' => now(),
            ]
        );
        $pkuAdmin->assignRole('tenant_admin');

        $school1 = School::firstOrCreate(
            ['tenant_id' => $tsinghua->id, 'code' => 'TH-CS'],
            [
                'name' => '清华大学计算机学院',
                'code' => 'TH-CS',
                'tenant_id' => $tsinghua->id,
                'status' => true,
            ]
        );

        $teacher1 = User::firstOrCreate(
            ['email' => 'th_teacher1@example.com'],
            [
                'name' => '张老师',
                'email' => 'th_teacher1@example.com',
                'password' => Hash::make('password123'),
                'type' => 'teacher',
                'tenant_id' => $tsinghua->id,
                'school_id' => $school1->id,
                'email_verified_at' => now(),
            ]
        );
        $teacher1->assignRole('teacher');

        $teacher2 = User::firstOrCreate(
            ['email' => 'th_teacher2@example.com'],
            [
                'name' => '李老师',
                'email' => 'th_teacher2@example.com',
                'password' => Hash::make('password123'),
                'type' => 'teacher',
                'tenant_id' => $tsinghua->id,
                'school_id' => $school1->id,
                'email_verified_at' => now(),
            ]
        );
        $teacher2->assignRole('teacher');

        $student1 = User::firstOrCreate(
            ['email' => 'th_student1@example.com'],
            [
                'name' => '王小明',
                'email' => 'th_student1@example.com',
                'password' => Hash::make('password123'),
                'type' => 'student',
                'tenant_id' => $tsinghua->id,
                'school_id' => $school1->id,
                'email_verified_at' => now(),
            ]
        );
        $student1->assignRole('student');

        $student2 = User::firstOrCreate(
            ['email' => 'th_student2@example.com'],
            [
                'name' => '李小红',
                'email' => 'th_student2@example.com',
                'password' => Hash::make('password123'),
                'type' => 'student',
                'tenant_id' => $tsinghua->id,
                'school_id' => $school1->id,
                'email_verified_at' => now(),
            ]
        );
        $student2->assignRole('student');
    }
}
