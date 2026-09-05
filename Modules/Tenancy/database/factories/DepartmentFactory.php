<?php

namespace Modules\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tenancy\Domain\Enums\DepartmentStatus;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Tenant;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company().' Department',
            'code' => strtoupper(fake()->unique()->bothify('DEP-###')),
            'status' => DepartmentStatus::Active,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepartmentStatus::Active,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepartmentStatus::Inactive,
        ]);
    }
}
