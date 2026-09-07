<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Application\Actions\Shifts\CreateShiftAction;
use Modules\HRM\Application\Actions\Shifts\GetShiftAction;
use Modules\HRM\Application\Actions\Shifts\ListShiftsAction;
use Modules\HRM\Application\Actions\Shifts\ToggleShiftStatusAction;
use Modules\HRM\Application\Actions\Shifts\UpdateShiftAction;
use Modules\HRM\Application\DTOs\CreateShiftData;
use Modules\HRM\Application\DTOs\UpdateShiftData;
use Modules\HRM\Models\Shift;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('creates a normal shift', function () {
    $tenant = Tenant::factory()->create();

    $shift = app(CreateShiftAction::class)->execute(
        new CreateShiftData(
            tenantId: $tenant->id,
            name: 'Morning Shift',
            code: 'MORNING',
            description: 'Regular morning shift',
            startTime: '09:00',
            endTime: '18:00',
            breakMinutes: 60,
            lateGraceMinutes: 10,
            earlyLeaveGraceMinutes: 10,
            isOvernight: false,
        )
    );

    expect($shift)
        ->tenant_id->toBe($tenant->id)
        ->name->toBe('Morning Shift')
        ->code->toBe('MORNING')
        ->is_overnight->toBeFalse()
        ->is_active->toBeTrue();
});

it('creates an overnight shift', function () {
    $tenant = Tenant::factory()->create();

    $shift = app(CreateShiftAction::class)->execute(
        new CreateShiftData(
            tenantId: $tenant->id,
            name: 'Night Shift',
            code: 'NIGHT',
            description: 'Overnight shift',
            startTime: '22:00',
            endTime: '06:00',
            breakMinutes: 60,
            lateGraceMinutes: 10,
            earlyLeaveGraceMinutes: 10,
            isOvernight: true,
        )
    );

    expect($shift->start_time)
        ->toBe('22:00')
        ->and($shift->end_time)
        ->toBe('06:00')
        ->and($shift->is_overnight)
        ->toBeTrue();
});

it('lists shifts only for the requested tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Shift::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Morning Shift',
    ]);

    Shift::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Night Shift',
    ]);

    $result = app(ListShiftsAction::class)->execute(
        tenantId: $tenantA->id,
    );

    expect($result->total())->toBe(1)
        ->and($result->first()->name)
        ->toBe('Morning Shift');
});

it('searches shifts by name or code', function () {
    $tenant = Tenant::factory()->create();

    Shift::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Morning Shift',
        'code' => 'MORNING',
    ]);

    Shift::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Night Shift',
        'code' => 'NIGHT',
    ]);

    $result = app(ListShiftsAction::class)->execute(
        tenantId: $tenant->id,
        search: 'NIGHT',
    );

    expect($result->total())->toBe(1)
        ->and($result->first()->code)
        ->toBe('NIGHT');
});

it('filters active shifts', function () {
    $tenant = Tenant::factory()->create();

    Shift::factory()->create([
        'tenant_id' => $tenant->id,
        'is_active' => true,
    ]);

    Shift::factory()->create([
        'tenant_id' => $tenant->id,
        'is_active' => false,
    ]);

    $result = app(ListShiftsAction::class)->execute(
        tenantId: $tenant->id,
        isActive: true,
    );

    expect($result->total())->toBe(1);
});

it('gets a shift belonging to the tenant', function () {
    $tenant = Tenant::factory()->create();

    $shift = Shift::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $result = app(GetShiftAction::class)->execute(
        tenantId: $tenant->id,
        publicId: $shift->public_id,
    );

    expect($result->public_id)
        ->toBe($shift->public_id);
});

it('cannot get another tenants shift', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $shift = Shift::factory()->create([
        'tenant_id' => $tenantB->id,
    ]);

    expect(fn () => app(GetShiftAction::class)->execute(
        tenantId: $tenantA->id,
        publicId: $shift->public_id,
    ))->toThrow(ModelNotFoundException::class);
});

it('updates a shift', function () {
    $tenant = Tenant::factory()->create();

    $shift = Shift::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Morning Shift',
    ]);

    $updated = app(UpdateShiftAction::class)->execute(
        new UpdateShiftData(
            tenantId: $tenant->id,
            publicId: $shift->public_id,
            name: 'Updated Morning Shift',
            code: 'UPDATED-MORNING',
            description: 'Updated description',
            startTime: '10:00',
            endTime: '19:00',
            breakMinutes: 60,
            lateGraceMinutes: 15,
            earlyLeaveGraceMinutes: 15,
            isOvernight: false,
            isActive: true,
        )
    );

    expect($updated->name)
        ->toBe('Updated Morning Shift')
        ->and($updated->code)
        ->toBe('UPDATED-MORNING')
        ->and($updated->start_time)
        ->toBe('10:00');
});

it('cannot update another tenants shift', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $shift = Shift::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Original Shift',
    ]);

    expect(fn () => app(UpdateShiftAction::class)->execute(
        new UpdateShiftData(
            tenantId: $tenantA->id,
            publicId: $shift->public_id,
            name: 'Hacked Shift',
            code: 'HACKED',
            description: null,
            startTime: '09:00',
            endTime: '18:00',
            breakMinutes: 60,
            lateGraceMinutes: 10,
            earlyLeaveGraceMinutes: 10,
            isOvernight: false,
            isActive: true,
        )
    ))->toThrow(ModelNotFoundException::class);

    expect($shift->fresh()->name)
        ->toBe('Original Shift');
});

it('deactivates an active shift', function () {
    $tenant = Tenant::factory()->create();

    $shift = Shift::factory()->create([
        'tenant_id' => $tenant->id,
        'is_active' => true,
    ]);

    $updated = app(ToggleShiftStatusAction::class)->execute(
        tenantId: $tenant->id,
        publicId: $shift->public_id,
    );

    expect($updated->is_active)->toBeFalse();
});

it('activates an inactive shift', function () {
    $tenant = Tenant::factory()->create();

    $shift = Shift::factory()->create([
        'tenant_id' => $tenant->id,
        'is_active' => false,
    ]);

    $updated = app(ToggleShiftStatusAction::class)->execute(
        tenantId: $tenant->id,
        publicId: $shift->public_id,
    );

    expect($updated->is_active)->toBeTrue();
});

it('cannot toggle another tenants shift', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $shift = Shift::factory()->create([
        'tenant_id' => $tenantB->id,
        'is_active' => true,
    ]);

    expect(fn () => app(ToggleShiftStatusAction::class)->execute(
        tenantId: $tenantA->id,
        publicId: $shift->public_id,
    ))->toThrow(ModelNotFoundException::class);

    expect($shift->fresh()->is_active)
        ->toBeTrue();
});
