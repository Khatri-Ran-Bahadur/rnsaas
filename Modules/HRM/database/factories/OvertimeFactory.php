<?php

namespace Modules\HRM\database\factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Domain\Enums\OvertimeStatus;
use Modules\HRM\Domain\Enums\OvertimeType;
use Modules\HRM\Models\Overtime;

/** @extends Factory<Overtime> */
class OvertimeFactory extends Factory
{
    protected $model = Overtime::class;

    public function definition(): array
    {
        return [
            'public_id' => fake()->uuid(),

            'tenant_id' => null,

            'tenant_staff_id' => null,

            'date' => fake()->dateTimeBetween(
                '-1 month',
                '+1 month'
            )->format('Y-m-d'),

            'start_time' => '18:00',

            'end_time' => '20:00',

            'total_minutes' => 120,

            'type' => OvertimeType::REGULAR,

            'status' => OvertimeStatus::PENDING,

            'rate_multiplier' => 1.00,

            'reason' => fake()->optional()->sentence(),

            'created_by' => null,

            'updated_by' => null,

            'approved_by' => null,

            'approved_at' => null,

            'rejected_by' => null,

            'rejected_at' => null,

            'rejection_reason' => null,

            'is_active' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => OvertimeStatus::PENDING,
        ]);
    }

    public function approved(?User $user = null): static
    {
        return $this->state([
            'status' => OvertimeStatus::APPROVED,
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);
    }

    public function rejected(?User $user = null): static
    {
        return $this->state([
            'status' => OvertimeStatus::REJECTED,
            'rejected_by' => $user?->id,
            'rejected_at' => now(),
            'rejection_reason' => 'Rejected by manager.',
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
