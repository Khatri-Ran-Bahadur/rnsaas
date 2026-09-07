<?php

namespace Modules\HRM\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Domain\Enums\HolidayType;
use Modules\HRM\Models\Holiday;

/** @extends Factory<Holiday> */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'public_id' => fake()->uuid(),
            'tenant_id' => null,
            'name' => fake()->unique()->words(2, true),
            'start_date' => fake()->dateTimeBetween('+1 week', '+1 year')
                ->format('Y-m-d'),
            'end_date' => null,
            'type' => HolidayType::PUBLIC,
            'description' => fake()->optional()->sentence(),
            'is_recurring' => false,
            'is_active' => true,
        ];
    }

    public function public(): static
    {
        return $this->state([
            'type' => HolidayType::PUBLIC,
        ]);
    }

    public function company(): static
    {
        return $this->state([
            'type' => HolidayType::COMPANY,
        ]);
    }

    public function optional(): static
    {
        return $this->state([
            'type' => HolidayType::OPTIONAL,
        ]);
    }

    public function recurring(): static
    {
        return $this->state([
            'is_recurring' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function multiDay(): static
    {
        return $this->state([
            'start_date' => now()->addMonth()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonth()->startOfMonth()->addDays(2)->toDateString(),
        ]);
    }
}
