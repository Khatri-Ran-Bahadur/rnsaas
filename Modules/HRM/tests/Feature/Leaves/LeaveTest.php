<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Domain\Enums\LeaveStatus;
use Modules\HRM\Domain\Enums\LeaveType;
use Modules\HRM\Models\LeaveRequest;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Models\TenantStaff;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->tenant = Tenant::factory()->create();

    $this->role = TenantRole::query()->firstOrCreate(
        [
            'tenant_id' => $this->tenant->id,
            'slug' => 'admin',
        ],
        [
            'name' => 'Admin',
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

    app(CurrentTenant::class)->set($this->tenant);

    $this->actingAs($this->user);
});

function createLeaveStaff(
    Tenant $tenant,
    User $user,
): TenantStaff {
    return TenantStaff::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'employment_status' => 'active',
    ]);
}

it('can create a leave request', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $response = $this->post(
        route('admin.hrm.leaves.store'),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::ANNUAL->value,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
            'reason' => 'Personal work',
        ],
    );

    $response->assertRedirect();

    $leave = LeaveRequest::first();

    expect($leave)->not->toBeNull()
        ->and($leave->tenant_id)
        ->toBe($this->tenant->id)
        ->and($leave->tenant_staff_id)
        ->toBe($staff->id)
        ->and($leave->total_days)
        ->toBe('3.00')
        ->and($leave->status)
        ->toBe(LeaveStatus::PENDING)
        ->and($leave->created_by)
        ->toBe($this->user->id);
});

it('calculates total leave days correctly', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $this->post(
        route('admin.hrm.leaves.store'),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::CASUAL->value,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
        ],
    );

    expect(
        LeaveRequest::first()->total_days,
    )->toBe('1.00');
});

it('rejects an invalid date range', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $response = $this->post(
        route('admin.hrm.leaves.store'),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::ANNUAL->value,
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-10',
        ],
    );

    $response->assertSessionHasErrors('end_date');
});

it('prevents overlapping pending leave', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    LeaveRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'start_date' => '2026-09-10',
        'end_date' => '2026-09-15',
        'status' => LeaveStatus::PENDING,
    ]);

    $response = $this->post(
        route('admin.hrm.leaves.store'),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::ANNUAL->value,
            'start_date' => '2026-09-12',
            'end_date' => '2026-09-14',
        ],
    );

    $response->assertStatus(422);

    expect(LeaveRequest::count())->toBe(1);
});

it('prevents overlapping approved leave', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    LeaveRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'start_date' => '2026-09-10',
        'end_date' => '2026-09-15',
        'status' => LeaveStatus::APPROVED,
    ]);

    $response = $this->post(
        route('admin.hrm.leaves.store'),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::ANNUAL->value,
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-18',
        ],
    );

    $response->assertStatus(422);

    expect(LeaveRequest::count())->toBe(1);
});

it('allows a new leave after a rejected leave', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    LeaveRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'start_date' => '2026-09-10',
        'end_date' => '2026-09-15',
        'status' => LeaveStatus::REJECTED,
    ]);

    $response = $this->post(
        route('admin.hrm.leaves.store'),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::ANNUAL->value,
            'start_date' => '2026-09-12',
            'end_date' => '2026-09-14',
        ],
    );

    $response->assertRedirect();

    expect(LeaveRequest::count())->toBe(2);
});

it('can list leave requests', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    LeaveRequest::factory()
        ->count(3)
        ->create([
            'tenant_id' => $this->tenant->id,
            'tenant_staff_id' => $staff->id,
        ]);

    $response = $this->get(
        route('admin.hrm.leaves.index'),
    );

    $response->assertOk();
});

it('can search leave requests by employee name', function (): void {
    $staffUser = User::factory()->create([
        'name' => 'Ram Bahadur',
    ]);

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    LeaveRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->get(
        route('admin.hrm.leaves.index', [
            'search' => 'Ram Bahadur',
        ]),
    );

    $response->assertOk();
});

it('can filter leaves by status', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    LeaveRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'status' => LeaveStatus::APPROVED,
    ]);

    $response = $this->get(
        route('admin.hrm.leaves.index', [
            'status' => LeaveStatus::APPROVED->value,
        ]),
    );

    $response->assertOk();
});

it('can show a leave request', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->get(
        route('admin.hrm.leaves.show', $leave),
    );

    $response->assertOk();
});

it('can open leave edit page', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->get(
        route('admin.hrm.leaves.edit', $leave),
    );

    $response->assertOk();
});

it('can update a pending leave request', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'start_date' => '2026-09-10',
        'end_date' => '2026-09-12',
        'status' => LeaveStatus::PENDING,
    ]);

    $response = $this->put(
        route('admin.hrm.leaves.update', $leave),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::SICK->value,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-22',
            'reason' => 'Medical leave',
            'is_active' => true,
        ],
    );

    $response->assertRedirect();

    $leave->refresh();

    expect($leave->leave_type)
        ->toBe(LeaveType::SICK)
        ->and($leave->total_days)
        ->toBe('3.00');
});

it('cannot update an approved leave request', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->approved()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->put(
        route('admin.hrm.leaves.update', $leave),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::SICK->value,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-22',
            'is_active' => true,
        ],
    );

    $response->assertStatus(422);
});

it('can approve a pending leave request', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->pending()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->patch(
        route('admin.hrm.leaves.approve', $leave),
    );

    $response->assertRedirect();

    $leave->refresh();

    expect($leave->status)
        ->toBe(LeaveStatus::APPROVED)
        ->and($leave->approved_by)
        ->toBe($this->user->id)
        ->and($leave->approved_at)
        ->not->toBeNull();
});

it('cannot approve an already approved leave', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->approved()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->patch(
        route('admin.hrm.leaves.approve', $leave),
    );

    $response->assertStatus(422);
});

it('can reject a pending leave request with a reason', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->pending()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->patch(
        route('admin.hrm.leaves.reject', $leave),
        [
            'rejection_reason' => 'Insufficient staffing.',
        ],
    );

    $response->assertRedirect();

    $leave->refresh();

    expect($leave->status)
        ->toBe(LeaveStatus::REJECTED)
        ->and($leave->rejected_by)
        ->toBe($this->user->id)
        ->and($leave->rejected_at)
        ->not->toBeNull()
        ->and($leave->rejection_reason)
        ->toBe('Insufficient staffing.');
});

it('requires a rejection reason', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->pending()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->patch(
        route('admin.hrm.leaves.reject', $leave),
        [],
    );

    $response->assertSessionHasErrors(
        'rejection_reason',
    );
});

it('cannot reject an already rejected leave', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->rejected()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->patch(
        route('admin.hrm.leaves.reject', $leave),
        [
            'rejection_reason' => 'Another reason.',
        ],
    );

    $response->assertStatus(422);
});

it('can toggle leave active status', function (): void {
    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $leave = LeaveRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'is_active' => true,
    ]);

    $response = $this->patch(
        route('admin.hrm.leaves.toggle-status', $leave),
    );

    $response->assertRedirect();

    expect(
        $leave->refresh()->is_active,
    )->toBeFalse();
});

it('cannot access a leave request from another tenant', function (): void {
    $otherTenant = Tenant::factory()->create();

    $otherUser = User::factory()->create();

    $otherStaff = createLeaveStaff(
        $otherTenant,
        $otherUser,
    );

    $leave = LeaveRequest::factory()->create([
        'tenant_id' => $otherTenant->id,
        'tenant_staff_id' => $otherStaff->id,
    ]);

    $response = $this->get(
        route('admin.hrm.leaves.show', $leave),
    );

    $response->assertNotFound();
});

it('cannot create leave for another tenant staff member', function (): void {
    $otherTenant = Tenant::factory()->create();

    $otherUser = User::factory()->create();

    $otherStaff = createLeaveStaff(
        $otherTenant,
        $otherUser,
    );

    $response = $this->post(
        route('admin.hrm.leaves.store'),
        [
            'tenant_staff_id' => $otherStaff->id,
            'leave_type' => LeaveType::ANNUAL->value,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
        ],
    );

    $response->assertNotFound();

    expect(LeaveRequest::count())->toBe(0);
});

it('cannot create leave for a suspended employee', function (): void {
    $staffUser = User::factory()->create();

    $staff = TenantStaff::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $staffUser->id,
        'employment_status' => 'suspended',
    ]);

    $response = $this->post(
        route('admin.hrm.leaves.store'),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::ANNUAL->value,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
        ],
    );

    $response->assertNotFound();
});

it('requires leave view permission', function (): void {
    $userWithoutPermission = User::factory()->create();
    $restrictedRole = TenantRole::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Restricted Leave',
        'slug' => 'restricted-leave',
        'permissions' => [],
        'is_system' => false,
        'is_active' => true,
    ]);
    TenantMembership::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $userWithoutPermission->id,
        'role_id' => $restrictedRole->id,
        'status' => 'active',
    ]);
    $this->actingAs($userWithoutPermission);

    $response = $this->get(
        route('admin.hrm.leaves.index'),
    );

    $response->assertForbidden();
});

it('requires leave manage permission', function (): void {
    $userWithoutPermission = User::factory()->create();
    $restrictedRole = TenantRole::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Restricted Leave Manage',
        'slug' => 'restricted-leave-manage',
        'permissions' => [],
        'is_system' => false,
        'is_active' => true,
    ]);
    TenantMembership::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $userWithoutPermission->id,
        'role_id' => $restrictedRole->id,
        'status' => 'active',
    ]);
    $this->actingAs($userWithoutPermission);

    $staffUser = User::factory()->create();

    $staff = createLeaveStaff(
        $this->tenant,
        $staffUser,
    );

    $response = $this->post(
        route('admin.hrm.leaves.store'),
        [
            'tenant_staff_id' => $staff->id,
            'leave_type' => LeaveType::ANNUAL->value,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
        ],
    );

    $response->assertForbidden();
});
