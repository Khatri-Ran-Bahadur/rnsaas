<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Domain\Enums\DayOfWeek;
use Modules\HRM\Models\WorkSchedule;
use Modules\HRM\Models\WorkScheduleDay;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('creates a working day for a work schedule', function () {
    $tenant = Tenant::factory()->create();

    $schedule = WorkSchedule::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $day = WorkScheduleDay::create([
        'work_schedule_id' => $schedule->id,
        'day_of_week' => DayOfWeek::MONDAY,
        'is_working_day' => true,
        'start_time' => '09:00',
        'end_time' => '18:00',
        'break_minutes' => 60,
    ]);

    expect($day->work_schedule_id)
        ->toBe($schedule->id)
        ->and($day->day_of_week)
        ->toBe(DayOfWeek::MONDAY)
        ->and($day->is_working_day)
        ->toBeTrue();
});

it('allows a non-working day without working hours', function () {
    $tenant = Tenant::factory()->create();

    $schedule = WorkSchedule::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $day = WorkScheduleDay::create([
        'work_schedule_id' => $schedule->id,
        'day_of_week' => DayOfWeek::SUNDAY,
        'is_working_day' => false,
        'start_time' => null,
        'end_time' => null,
        'break_minutes' => 0,
    ]);

    expect($day->is_working_day)
        ->toBeFalse()
        ->and($day->start_time)
        ->toBeNull()
        ->and($day->end_time)
        ->toBeNull();
});

it('does not allow duplicate days in the same schedule', function () {
    $tenant = Tenant::factory()->create();

    $schedule = WorkSchedule::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    WorkScheduleDay::create([
        'work_schedule_id' => $schedule->id,
        'day_of_week' => DayOfWeek::MONDAY,
        'is_working_day' => true,
        'start_time' => '09:00',
        'end_time' => '18:00',
        'break_minutes' => 60,
    ]);

    expect(fn () => WorkScheduleDay::create([
        'work_schedule_id' => $schedule->id,
        'day_of_week' => DayOfWeek::MONDAY,
        'is_working_day' => true,
        'start_time' => '10:00',
        'end_time' => '19:00',
        'break_minutes' => 60,
    ]))->toThrow(QueryException::class);
});
