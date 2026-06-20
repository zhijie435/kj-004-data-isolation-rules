<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => fake()->unique()->slug(3),
            'status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
