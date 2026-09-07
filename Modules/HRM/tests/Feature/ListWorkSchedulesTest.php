<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Application\Actions\ListWorkSchedulesAction;
use Modules\HRM\Models\WorkSchedule;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

it('only returns work schedules belonging to the requested tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    WorkSchedule::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Tenant A Schedule',
    ]);

    WorkSchedule::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B Schedule',
    ]);

    $result = app(ListWorkSchedulesAction::class)->execute(
        tenantId: $tenantA->id,
    );

    expect($result->total())->toBe(1)
        ->and($result->first()->name)
        ->toBe('Tenant A Schedule');
});

it('can search work schedules by name', function () {
    $tenant = Tenant::factory()->create();

    WorkSchedule::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'General Office',
    ]);

    WorkSchedule::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Night Shift',
    ]);

    $result = app(ListWorkSchedulesAction::class)->execute(
        tenantId: $tenant->id,
        search: 'General',
    );

    expect($result->total())->toBe(1)
        ->and($result->first()->name)
        ->toBe('General Office');
});

it('can filter active work schedules', function () {
    $tenant = Tenant::factory()->create();

    WorkSchedule::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Active Schedule',
        'is_active' => true,
    ]);

    WorkSchedule::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Inactive Schedule',
        'is_active' => false,
    ]);

    $result = app(ListWorkSchedulesAction::class)->execute(
        tenantId: $tenant->id,
        isActive: true,
    );

    expect($result->total())->toBe(1)
        ->and($result->first()->name)
        ->toBe('Active Schedule');
});
