<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Domain\Enums\OvertimeStatus;
use Modules\HRM\Domain\Enums\OvertimeType;
use Modules\HRM\Models\Overtime;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Models\TenantStaff;

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
    app(CurrentTenant::class)->set($this->tenant);

    $this->staff = TenantStaff::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'employment_status' => 'active',
    ]);
});

/*
|--------------------------------------------------------------------------
| Create / Store
|--------------------------------------------------------------------------
*/

it('can create an overtime request', function (): void {
    $response = $this->post(
        route('admin.hrm.overtimes.store'),
        [
            'tenant_staff_id' => $this->staff->id,
            'date' => '2026-09-10',
            'start_time' => '18:00',
            'end_time' => '20:00',
            'type' => OvertimeType::REGULAR->value,
            'rate_multiplier' => 1.5,
            'reason' => 'Project release overtime',
        ]
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('overtimes', [
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'date' => '2026-09-10',
        'start_time' => '18:00',
        'end_time' => '20:00',
        'total_minutes' => 120,
        'type' => OvertimeType::REGULAR->value,
        'status' => OvertimeStatus::PENDING->value,
        'rate_multiplier' => 1.5,
        'reason' => 'Project release overtime',
        'is_active' => true,
    ]);
});

it('rejects overtime when start and end time are the same', function (): void {
    $response = $this->post(
        route('admin.hrm.overtimes.store'),
        [
            'tenant_staff_id' => $this->staff->id,
            'date' => '2026-09-10',
            'start_time' => '18:00',
            'end_time' => '18:00',
            'type' => OvertimeType::REGULAR->value,
            'rate_multiplier' => 1.0,
            'reason' => 'Zero minute overtime',
        ]
    );

    $response->assertSessionHasErrors('end_time');
});

/*
|--------------------------------------------------------------------------
| Index / List
|--------------------------------------------------------------------------
*/

it('can list overtimes for current tenant', function (): void {
    Overtime::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->get(route('admin.hrm.overtimes.index'));

    $response->assertOk();
});

it('only lists overtimes belonging to current tenant', function (): void {
    $otherTenant = Tenant::factory()->create();
    $otherStaff = TenantStaff::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    Overtime::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'reason' => 'My Tenant Overtime',
    ]);

    Overtime::factory()->create([
        'tenant_id' => $otherTenant->id,
        'tenant_staff_id' => $otherStaff->id,
        'reason' => 'Other Tenant Overtime',
    ]);

    $response = $this->get(route('admin.hrm.overtimes.index'));

    $response->assertOk();

    expect(
        Overtime::query()
            ->forTenant($this->tenant->id)
            ->where('reason', 'Other Tenant Overtime')
            ->exists()
    )->toBeFalse();
});

it('can filter overtimes by status', function (): void {
    Overtime::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'status' => OvertimeStatus::PENDING,
    ]);

    Overtime::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'status' => OvertimeStatus::APPROVED,
        'approved_by' => $this->user->id,
        'approved_at' => now(),
    ]);

    $response = $this->get(route('admin.hrm.overtimes.index', [
        'status' => OvertimeStatus::PENDING->value,
    ]));

    $response->assertOk();
});

/*
|--------------------------------------------------------------------------
| Show / Edit
|--------------------------------------------------------------------------
*/

it('can show an overtime record', function (): void {
    $overtime = Overtime::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->get(route('admin.hrm.overtimes.show', $overtime));

    $response->assertOk();
});

it('cannot show another tenant overtime', function (): void {
    $otherTenant = Tenant::factory()->create();
    $otherStaff = TenantStaff::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $overtime = Overtime::factory()->create([
        'tenant_id' => $otherTenant->id,
        'tenant_staff_id' => $otherStaff->id,
    ]);

    $response = $this->get(route('admin.hrm.overtimes.show', $overtime));

    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

it('can update an overtime record', function (): void {
    $overtime = Overtime::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'start_time' => '18:00',
        'end_time' => '19:00',
        'total_minutes' => 60,
    ]);

    $response = $this->put(
        route('admin.hrm.overtimes.update', $overtime),
        [
            'tenant_staff_id' => $this->staff->id,
            'date' => '2026-09-11',
            'start_time' => '18:00',
            'end_time' => '21:00',
            'type' => OvertimeType::WEEKEND->value,
            'rate_multiplier' => 2.0,
            'reason' => 'Updated overtime reason',
            'is_active' => true,
        ]
    );

    $response->assertRedirect();

    $overtime->refresh();

    expect($overtime->date->toDateString())->toBe('2026-09-11')
        ->and($overtime->total_minutes)->toBe(180)
        ->and($overtime->type)->toBe(OvertimeType::WEEKEND)
        ->and((float) $overtime->rate_multiplier)->toBe(2.0);
});

/*
|--------------------------------------------------------------------------
| Toggle Status
|--------------------------------------------------------------------------
*/

it('can toggle overtime active status', function (): void {
    $overtime = Overtime::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'is_active' => true,
    ]);

    $response = $this->patch(route('admin.hrm.overtimes.toggle-status', $overtime));

    $response->assertRedirect();

    expect($overtime->refresh()->is_active)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Approve / Reject
|--------------------------------------------------------------------------
*/

it('can approve a pending overtime', function (): void {
    $overtime = Overtime::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'status' => OvertimeStatus::PENDING,
    ]);

    $response = $this->patch(route('admin.hrm.overtimes.approve', $overtime));

    $response->assertRedirect();

    $overtime->refresh();

    expect($overtime->isApproved())->toBeTrue()
        ->and($overtime->approved_by)->toBe($this->user->id)
        ->and($overtime->approved_at)->not->toBeNull();
});

it('can reject a pending overtime', function (): void {
    $overtime = Overtime::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $this->staff->id,
        'status' => OvertimeStatus::PENDING,
    ]);

    $response = $this->patch(route('admin.hrm.overtimes.reject', $overtime), [
        'rejection_reason' => 'Not approved by team lead.',
    ]);

    $response->assertRedirect();

    $overtime->refresh();

    expect($overtime->isRejected())->toBeTrue()
        ->and($overtime->rejected_by)->toBe($this->user->id)
        ->and($overtime->rejection_reason)->toBe('Not approved by team lead.')
        ->and($overtime->rejected_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Model domain behavior
|--------------------------------------------------------------------------
*/

it('calculates total hours from total minutes', function (): void {
    $overtime = Overtime::factory()->make([
        'total_minutes' => 150,
    ]);

    expect($overtime->totalHours())->toBe(2.5);
});
