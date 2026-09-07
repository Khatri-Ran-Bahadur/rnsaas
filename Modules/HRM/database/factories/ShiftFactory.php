<?php

namespace Modules\HRM\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\Shift;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'public_id' => fake()->uuid(),
            'tenant_id' => null,
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('SHIFT-????')),
            'description' => fake()->optional()->sentence(),
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_minutes' => 60,
            'late_grace_minutes' => 10,
            'early_leave_grace_minutes' => 10,
            'is_overnight' => false,
            'is_active' => true,
        ];
    }

    public function overnight(): static
    {
        return $this->state([
            'start_time' => '22:00',
            'end_time' => '06:00',
            'is_overnight' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
