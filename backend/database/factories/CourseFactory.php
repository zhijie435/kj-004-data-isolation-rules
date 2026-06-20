<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'school_id' => null,
            'teacher_id' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => true,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function forSchool(School $school): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
            'tenant_id' => $school->tenant_id,
        ]);
    }

    public function taughtBy(User $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id,
            'tenant_id' => $teacher->tenant_id ?? $attributes['tenant_id'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
