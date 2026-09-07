<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Application\Actions\ToggleWorkScheduleStatusAction;
use Modules\HRM\Application\Actions\UpdateWorkScheduleAction;
use Modules\HRM\Application\DTOs\UpdateWorkScheduleData;
use Modules\HRM\Models\WorkSchedule;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('cannot update a work schedule belonging to another tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $schedule = WorkSchedule::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B Schedule',
    ]);

    $data = new UpdateWorkScheduleData(
        tenantId: $tenantA->id,
        publicId: $schedule->public_id,
        name: 'Hacked Schedule',
        description: null,
        timezone: 'Asia/Kathmandu',
        defaultStartTime: '09:00',
        defaultEndTime: '18:00',
        breakMinutes: 60,
        lateGraceMinutes: 10,
        earlyLeaveGraceMinutes: 10,
        isActive: true,
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

    expect(fn () => app(UpdateWorkScheduleAction::class)->execute($data))
        ->toThrow(ModelNotFoundException::class);

    expect($schedule->fresh()->name)
        ->toBe('Tenant B Schedule');
});

it('deactivates an active work schedule', function () {
    $tenant = Tenant::factory()->create();

    $schedule = WorkSchedule::factory()->create([
        'tenant_id' => $tenant->id,
        'is_active' => true,
    ]);

    $updated = app(ToggleWorkScheduleStatusAction::class)->execute(
        tenantId: $tenant->id,
        publicId: $schedule->public_id,
    );

    expect($updated->is_active)->toBeFalse();
});

it('activates an inactive work schedule', function () {
    $tenant = Tenant::factory()->create();

    $schedule = WorkSchedule::factory()->create([
        'tenant_id' => $tenant->id,
        'is_active' => false,
    ]);

    $updated = app(ToggleWorkScheduleStatusAction::class)->execute(
        tenantId: $tenant->id,
        publicId: $schedule->public_id,
    );

    expect($updated->is_active)->toBeTrue();
});

it('cannot toggle another tenants work schedule', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $schedule = WorkSchedule::factory()->create([
        'tenant_id' => $tenantB->id,
        'is_active' => true,
    ]);

    expect(fn () => app(ToggleWorkScheduleStatusAction::class)->execute(
        tenantId: $tenantA->id,
        publicId: $schedule->public_id,
    ))->toThrow(
        ModelNotFoundException::class
    );

    expect($schedule->fresh()->is_active)
        ->toBeTrue();
});
