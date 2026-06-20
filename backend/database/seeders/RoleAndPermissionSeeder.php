<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_rules',
            'create_rules',
            'edit_rules',
            'delete_rules',
            'toggle_rules',
            'test_rules',
            'view_courses',
            'view_classes',
            'view_users',
            'manage_tenants',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo($permissions);

        $tenantAdmin = Role::create(['name' => 'tenant_admin', 'guard_name' => 'web']);
        $tenantAdmin->givePermissionTo([
            'view_rules',
            'create_rules',
            'edit_rules',
            'toggle_rules',
            'test_rules',
            'view_courses',
            'view_classes',
            'view_users',
        ]);

        $teacher = Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        $teacher->givePermissionTo([
            'view_rules',
            'view_courses',
            'view_classes',
        ]);

        $student = Role::create(['name' => 'student', 'guard_name' => 'web']);
        $student->givePermissionTo([
            'view_courses',
            'view_classes',
        ]);
    }
}
