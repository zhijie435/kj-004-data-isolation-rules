<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company() . ' School',
            'code' => fake()->unique()->slug(2),
            'status' => true,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
