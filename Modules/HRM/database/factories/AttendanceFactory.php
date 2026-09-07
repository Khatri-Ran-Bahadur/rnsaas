<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Domain\Enums\AttendanceSource;
use Modules\HRM\Domain\Enums\AttendanceStatus;
use Modules\HRM\Models\Attendance;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'tenant_staff_id' => null,

            'attendance_date' => fake()->date(),

            'check_in' => '09:00',
            'check_out' => '17:00',

            'worked_minutes' => 480,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,

            'status' => AttendanceStatus::PRESENT,
            'source' => AttendanceSource::MANUAL,

            'notes' => null,

            'created_by' => null,
            'updated_by' => null,

            'is_active' => true,
        ];
    }

    public function late(): static
    {
        return $this->state([
            'status' => AttendanceStatus::LATE,
            'late_minutes' => 30,
            'check_in' => '09:30',
        ]);
    }

    public function absent(): static
    {
        return $this->state([
            'status' => AttendanceStatus::ABSENT,
            'check_in' => null,
            'check_out' => null,
            'worked_minutes' => 0,
        ]);
    }

    public function halfDay(): static
    {
        return $this->state([
            'status' => AttendanceStatus::HALF_DAY,
            'check_in' => '09:00',
            'check_out' => '13:00',
            'worked_minutes' => 240,
        ]);
    }

    public function mobile(): static
    {
        return $this->state([
            'source' => AttendanceSource::MOBILE,
        ]);
    }

    public function biometric(): static
    {
        return $this->state([
            'source' => AttendanceSource::BIOMETRIC,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
