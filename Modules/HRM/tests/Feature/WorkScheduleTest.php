<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Models\WorkSchedule;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('creates a work schedule for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $schedule = WorkSchedule::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'General Office',
        'description' => null,
        'timezone' => 'Asia/Kathmandu',
        'default_start_time' => '09:00',
        'default_end_time' => '18:00',
        'break_minutes' => 60,
        'late_grace_minutes' => 10,
        'early_leave_grace_minutes' => 10,
        'is_active' => true,
    ]);

    expect($schedule)
        ->toBeInstanceOf(WorkSchedule::class)
        ->tenant_id->toBe($tenant->id)
        ->name->toBe('General Office')
        ->is_active->toBeTrue();
});
