<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Domain\Enums\HolidayType;
use Modules\HRM\Models\Holiday;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();

    $this->user = User::factory()->create();

    $this->role = TenantRole::query()->firstOrCreate(
        [
            'tenant_id' => $this->tenant->id,
            'slug' => 'admin',
        ],
        [
            'name' => 'Admin',
            'description' => 'Organization administrator',
            'is_system' => true,
            'is_active' => true,
        ]
    );

    TenantMembership::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'role_id' => $this->role->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->user);

    session(['current_tenant_id' => $this->tenant->id]);
});

/*
|--------------------------------------------------------------------------
| Create
|--------------------------------------------------------------------------
*/

it('can create a holiday', function (): void {
    $response = $this->post(
        route('admin.hrm.holidays.store'),
        [
            'name' => 'Dashain',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'type' => HolidayType::PUBLIC->value,
            'description' => 'Dashain holiday',
            'is_recurring' => false,
        ]
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('holidays', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Dashain',
        'start_date' => '2026-10-20',
        'end_date' => '2026-10-22',
        'type' => HolidayType::PUBLIC->value,
        'is_recurring' => false,
        'is_active' => true,
    ]);
});

it('can create a recurring holiday', function (): void {
    $response = $this->post(
        route('admin.hrm.holidays.store'),
        [
            'name' => 'New Year',
            'start_date' => '2027-01-01',
            'end_date' => null,
            'type' => HolidayType::PUBLIC->value,
            'description' => null,
            'is_recurring' => true,
        ]
    );

    $response->assertRedirect();

    $holiday = Holiday::query()
        ->where('tenant_id', $this->tenant->id)
        ->where('name', 'New Year')
        ->first();

    expect($holiday)->not->toBeNull()
        ->and($holiday->is_recurring)->toBeTrue()
        ->and($holiday->end_date)->toBeNull();
});

it('rejects recurring holiday with an end date', function (): void {
    $response = $this->post(
        route('admin.hrm.holidays.store'),
        [
            'name' => 'Invalid Recurring Holiday',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'type' => HolidayType::PUBLIC->value,
            'description' => null,
            'is_recurring' => true,
        ]
    );

    $response->assertSessionHasErrors('end_date');
});

it('rejects end date before start date', function (): void {
    $response = $this->post(
        route('admin.hrm.holidays.store'),
        [
            'name' => 'Invalid Holiday',
            'start_date' => '2026-10-22',
            'end_date' => '2026-10-20',
            'type' => HolidayType::PUBLIC->value,
            'description' => null,
            'is_recurring' => false,
        ]
    );

    $response->assertSessionHasErrors('end_date');
});

it('rejects the same start and end date when recurring', function (): void {
    $response = $this->post(
        route('admin.hrm.holidays.store'),
        [
            'name' => 'Recurring Holiday',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'type' => HolidayType::PUBLIC->value,
            'description' => null,
            'is_recurring' => true,
        ]
    );

    $response->assertSessionHasErrors('end_date');
});

/*
|--------------------------------------------------------------------------
| Index
|--------------------------------------------------------------------------
*/

it('can list holidays', function (): void {
    Holiday::factory()
        ->count(3)
        ->create([
            'tenant_id' => $this->tenant->id,
        ]);

    $response = $this->get(
        route('admin.hrm.holidays.index')
    );

    $response->assertOk();
});

it('only lists holidays belonging to the current tenant', function (): void {
    $otherTenant = Tenant::factory()->create();

    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'My Holiday',
    ]);

    Holiday::factory()->create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Other Tenant Holiday',
    ]);

    $response = $this->get(
        route('admin.hrm.holidays.index', [
            'search' => 'Other Tenant Holiday',
        ])
    );

    $response->assertOk();

    expect(
        Holiday::query()
            ->forTenant($this->tenant->id)
            ->where('name', 'Other Tenant Holiday')
            ->exists()
    )->toBeFalse();
});

it('can search holidays by name', function (): void {
    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Dashain Festival',
    ]);

    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Company Foundation Day',
    ]);

    $response = $this->get(
        route('admin.hrm.holidays.index', [
            'search' => 'Dashain',
        ])
    );

    $response->assertOk();
});

it('can search holidays by description', function (): void {
    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Special Holiday',
        'description' => 'Annual company celebration',
    ]);

    $response = $this->get(
        route('admin.hrm.holidays.index', [
            'search' => 'company celebration',
        ])
    );

    $response->assertOk();
});

it('can filter holidays by type', function (): void {
    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'type' => HolidayType::PUBLIC,
    ]);

    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'type' => HolidayType::COMPANY,
    ]);

    $response = $this->get(
        route('admin.hrm.holidays.index', [
            'type' => HolidayType::PUBLIC->value,
        ])
    );

    $response->assertOk();
});

it('can filter active holidays', function (): void {
    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => true,
    ]);

    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => false,
    ]);

    $response = $this->get(
        route('admin.hrm.holidays.index', [
            'active' => 'true',
        ])
    );

    $response->assertOk();
});

/*
|--------------------------------------------------------------------------
| Show / Edit
|--------------------------------------------------------------------------
*/

it('can show a holiday', function (): void {
    $holiday = Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->get(
        route('admin.hrm.holidays.show', $holiday)
    );

    $response->assertOk();
});

it('can open the edit page for a holiday', function (): void {
    $holiday = Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->get(
        route('admin.hrm.holidays.edit', $holiday)
    );

    $response->assertOk();
});

it('cannot show another tenant holiday', function (): void {
    $otherTenant = Tenant::factory()->create();

    $holiday = Holiday::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $response = $this->get(
        route('admin.hrm.holidays.show', $holiday)
    );

    $response->assertNotFound();
});

it('cannot edit another tenant holiday', function (): void {
    $otherTenant = Tenant::factory()->create();

    $holiday = Holiday::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $response = $this->get(
        route('admin.hrm.holidays.edit', $holiday)
    );

    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

it('can update a holiday', function (): void {
    $holiday = Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Old Holiday',
    ]);

    $response = $this->put(
        route('admin.hrm.holidays.update', $holiday),
        [
            'name' => 'Updated Holiday',
            'start_date' => '2026-12-25',
            'end_date' => null,
            'type' => HolidayType::COMPANY->value,
            'description' => 'Updated description',
            'is_recurring' => false,
            'is_active' => true,
        ]
    );

    $response->assertRedirect();

    $holiday->refresh();

    expect($holiday->name)
        ->toBe('Updated Holiday')
        ->and($holiday->type)
        ->toBe(HolidayType::COMPANY)
        ->and($holiday->description)
        ->toBe('Updated description')
        ->and($holiday->is_active)
        ->toBeTrue();
});

it('cannot update another tenant holiday', function (): void {
    $otherTenant = Tenant::factory()->create();

    $holiday = Holiday::factory()->create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Original Holiday',
    ]);

    $response = $this->put(
        route('admin.hrm.holidays.update', $holiday),
        [
            'name' => 'Hacked Holiday',
            'start_date' => '2026-12-25',
            'end_date' => null,
            'type' => HolidayType::PUBLIC->value,
            'description' => null,
            'is_recurring' => false,
            'is_active' => true,
        ]
    );

    $response->assertNotFound();

    expect($holiday->refresh()->name)
        ->toBe('Original Holiday');
});

/*
|--------------------------------------------------------------------------
| Toggle Status
|--------------------------------------------------------------------------
*/

it('can deactivate a holiday', function (): void {
    $holiday = Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => true,
    ]);

    $response = $this->patch(
        route('admin.hrm.holidays.toggle-status', $holiday)
    );

    $response->assertRedirect();

    expect($holiday->refresh()->is_active)
        ->toBeFalse();
});

it('can activate an inactive holiday', function (): void {
    $holiday = Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => false,
    ]);

    $response = $this->patch(
        route('admin.hrm.holidays.toggle-status', $holiday)
    );

    $response->assertRedirect();

    expect($holiday->refresh()->is_active)
        ->toBeTrue();
});

it('cannot toggle another tenant holiday', function (): void {
    $otherTenant = Tenant::factory()->create();

    $holiday = Holiday::factory()->create([
        'tenant_id' => $otherTenant->id,
        'is_active' => true,
    ]);

    $response = $this->patch(
        route('admin.hrm.holidays.toggle-status', $holiday)
    );

    $response->assertNotFound();

    expect($holiday->refresh()->is_active)
        ->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Domain behaviour
|--------------------------------------------------------------------------
*/

it('detects a multi day holiday', function (): void {
    $holiday = Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'start_date' => '2026-10-20',
        'end_date' => '2026-10-22',
    ]);

    expect($holiday->isMultiDay())
        ->toBeTrue();
});

it('detects a single day holiday', function (): void {
    $holiday = Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'start_date' => '2026-10-20',
        'end_date' => null,
    ]);

    expect($holiday->isMultiDay())
        ->toBeFalse();
});

it('can determine whether a holiday covers a date', function (): void {
    $holiday = Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'start_date' => '2026-10-20',
        'end_date' => '2026-10-22',
    ]);

    expect($holiday->coversDate('2026-10-20'))->toBeTrue()
        ->and($holiday->coversDate('2026-10-21'))->toBeTrue()
        ->and($holiday->coversDate('2026-10-22'))->toBeTrue()
        ->and($holiday->coversDate('2026-10-23'))->toBeFalse();
});

it('can find an active holiday covering a date', function (): void {
    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'start_date' => '2026-10-20',
        'end_date' => '2026-10-22',
        'is_active' => true,
    ]);

    expect(
        Holiday::query()
            ->forTenant($this->tenant->id)
            ->active()
            ->coveringDate('2026-10-21')
            ->exists()
    )->toBeTrue();
});

it('does not find an inactive holiday using active scope', function (): void {
    Holiday::factory()->create([
        'tenant_id' => $this->tenant->id,
        'start_date' => '2026-10-20',
        'end_date' => '2026-10-22',
        'is_active' => false,
    ]);

    expect(
        Holiday::query()
            ->forTenant($this->tenant->id)
            ->active()
            ->coveringDate('2026-10-21')
            ->exists()
    )->toBeFalse();
});
