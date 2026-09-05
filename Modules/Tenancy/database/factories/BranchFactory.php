<?php

namespace Modules\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tenancy\Domain\Enums\BranchStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Tenant;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => fake()->city().' Main Branch',
            'code' => strtoupper(fake()->unique()->bothify('BR-###')),
            'status' => BranchStatus::Active,
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->countryCode(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BranchStatus::Active,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BranchStatus::Inactive,
        ]);
    }
}
