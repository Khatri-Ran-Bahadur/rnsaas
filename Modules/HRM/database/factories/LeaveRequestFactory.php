<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Domain\Enums\LeaveStatus;
use Modules\HRM\Domain\Enums\LeaveType;
use Modules\HRM\Models\LeaveRequest;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween(
            'now',
            '+30 days',
        );

        $days = fake()->numberBetween(1, 5);

        $end = (clone $start);
        $end->modify('+'.($days - 1).' days');

        return [
            'tenant_id' => null,
            'tenant_staff_id' => null,

            'leave_type' => fake()->randomElement(
                LeaveType::cases(),
            ),

            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),

            'total_days' => $days,

            'reason' => fake()->optional()->sentence(),

            'status' => LeaveStatus::PENDING,

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
            'status' => LeaveStatus::PENDING,
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => LeaveStatus::APPROVED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => LeaveStatus::REJECTED,
            'rejected_at' => now(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
