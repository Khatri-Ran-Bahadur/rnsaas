<?php

namespace Modules\HRM\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\WorkSchedule;

class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        return [
            'public_id' => fake()->uuid(),
            'tenant_id' => null,
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'timezone' => 'Asia/Kathmandu',
            'default_start_time' => '09:00',
            'default_end_time' => '18:00',
            'break_minutes' => 60,
            'late_grace_minutes' => 10,
            'early_leave_grace_minutes' => 10,
            'is_active' => true,
        ];
    }
}
