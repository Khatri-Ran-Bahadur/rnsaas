<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Application\Actions\CreateWorkScheduleAction;
use Modules\HRM\Application\DTOs\CreateWorkScheduleData;
use Modules\HRM\Models\WorkSchedule;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('creates a work schedule with weekly rules in one transaction', function () {
    $tenant = Tenant::factory()->create();

    $data = new CreateWorkScheduleData(
        tenantId: $tenant->id,
        name: 'General Office',
        description: 'Standard office working schedule',
        timezone: 'Asia/Kathmandu',
        defaultStartTime: '09:00',
        defaultEndTime: '18:00',
        breakMinutes: 60,
        lateGraceMinutes: 10,
        earlyLeaveGraceMinutes: 10,
        days: [
            [
                'day_of_week' => 1,
                'is_working_day' => true,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'break_minutes' => 60,
            ],
            [
                'day_of_week' => 2,
                'is_working_day' => true,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'break_minutes' => 60,
            ],
            [
                'day_of_week' => 3,
                'is_working_day' => true,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'break_minutes' => 60,
            ],
            [
                'day_of_week' => 4,
                'is_working_day' => true,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'break_minutes' => 60,
            ],
            [
                'day_of_week' => 5,
                'is_working_day' => true,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'break_minutes' => 60,
            ],
            [
                'day_of_week' => 6,
                'is_working_day' => false,
                'start_time' => null,
                'end_time' => null,
                'break_minutes' => 0,
            ],
            [
                'day_of_week' => 7,
                'is_working_day' => false,
                'start_time' => null,
                'end_time' => null,
                'break_minutes' => 0,
            ],
        ],
    );

    $schedule = app(CreateWorkScheduleAction::class)->execute($data);

    expect($schedule)
        ->toBeInstanceOf(WorkSchedule::class)
        ->tenant_id->toBe($tenant->id)
        ->name->toBe('General Office');

    expect($schedule->days)
        ->toHaveCount(7);

    expect($schedule->days->where('is_working_day', true))
        ->toHaveCount(5);

    expect($schedule->days->where('is_working_day', false))
        ->toHaveCount(2);
});
