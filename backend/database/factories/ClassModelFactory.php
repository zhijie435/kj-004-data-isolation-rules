<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\School;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassModelFactory extends Factory
{
    protected $model = \App\Models\ClassModel::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'school_id' => null,
            'course_id' => null,
            'teacher_id' => null,
            'head_teacher_id' => null,
            'name' => fake()->word() . ' Class',
            'grade' => fake()->randomElement(['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5']),
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

    public function forCourse(Course $course): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => $course->id,
            'tenant_id' => $course->tenant_id,
            'school_id' => $course->school_id,
        ]);
    }

    public function withTeacher(User $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id,
            'tenant_id' => $teacher->tenant_id ?? $attributes['tenant_id'],
        ]);
    }

    public function withHeadTeacher(User $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'head_teacher_id' => $teacher->id,
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
