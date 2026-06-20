<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'name' => '清华大学',
                'code' => 'Tsinghua',
                'status' => true,
            ],
            [
                'name' => '北京大学',
                'code' => 'PKU',
                'status' => true,
            ],
            [
                'name' => '复旦大学',
                'code' => 'Fudan',
                'status' => true,
            ],
        ];

        foreach ($tenants as $tenant) {
            Tenant::firstOrCreate(['code' => $tenant['code']], $tenant);
        }
    }
}
