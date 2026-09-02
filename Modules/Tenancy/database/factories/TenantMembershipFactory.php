<?php

namespace Modules\Tenancy\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

/**
 * @extends Factory<TenantMembership>
 */
class TenantMembershipFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TenantMembership>
     */
    protected $model = TenantMembership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'status' => TenantMembershipStatus::Active,
            'joined_at' => now(),
            'invited_at' => now(),
            'suspended_at' => null,
            'revoked_at' => null,
            'invited_by' => null,
            'suspended_by' => null,
            'revoked_by' => null,
            'settings' => [],
            'version' => 1,
        ];
    }

    /**
     * Indicate that the membership is invited.
     */
    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantMembershipStatus::Invited,
            'joined_at' => null,
            'invited_at' => now(),
        ]);
    }

    /**
     * Indicate that the membership is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantMembershipStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }

    /**
     * Indicate that the membership is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantMembershipStatus::Revoked,
            'revoked_at' => now(),
        ]);
    }
}
