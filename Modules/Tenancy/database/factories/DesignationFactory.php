<?php

namespace Modules\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tenancy\Domain\Enums\DesignationStatus;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;

/**
 * @extends Factory<Designation>
 */
class DesignationFactory extends Factory
{
    protected $model = Designation::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => fake()->jobTitle(),
            'code' => strtoupper(fake()->unique()->bothify('DES-###')),
            'status' => DesignationStatus::Active,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignationStatus::Active,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignationStatus::Inactive,
        ]);
    }
}
