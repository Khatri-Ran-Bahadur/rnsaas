<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HRM\Domain\Enums\AttendanceSource;
use Modules\HRM\Domain\Enums\AttendanceStatus;
use Modules\HRM\Models\Attendance;
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

function createAttendanceStaff(
    Tenant $tenant,
    User $user,
): TenantStaff {
    return TenantStaff::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'employment_status' => 'active',
    ]);
}

it('can create attendance', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    $response = $this->post(
        route('admin.hrm.attendances.store'),
        [
            'tenant_staff_id' => $staff->id,
            'attendance_date' => '2026-09-07',
            'check_in' => '09:00',
            'check_out' => '17:00',
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'status' => AttendanceStatus::PRESENT->value,
            'source' => AttendanceSource::MANUAL->value,
            'notes' => 'Regular attendance',
        ],
    );

    $response->assertRedirect();

    $attendance = Attendance::first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->tenant_id)
        ->toBe($this->tenant->id)
        ->and($attendance->tenant_staff_id)
        ->toBe($staff->id)
        ->and($attendance->worked_minutes)
        ->toBe(480)
        ->and($attendance->status)
        ->toBe(AttendanceStatus::PRESENT)
        ->and($attendance->created_by)
        ->toBe($this->user->id);
});

it('calculates overnight worked minutes correctly', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    $this->post(
        route('admin.hrm.attendances.store'),
        [
            'tenant_staff_id' => $staff->id,
            'attendance_date' => '2026-09-07',
            'check_in' => '22:00',
            'check_out' => '06:00',
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'status' => AttendanceStatus::PRESENT->value,
            'source' => AttendanceSource::MANUAL->value,
        ],
    );

    expect(
        Attendance::first()->worked_minutes,
    )->toBe(480);
});

it('cannot create duplicate attendance for the same employee and date', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    Attendance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'attendance_date' => '2026-09-07',
    ]);

    $response = $this->post(
        route('admin.hrm.attendances.store'),
        [
            'tenant_staff_id' => $staff->id,
            'attendance_date' => '2026-09-07',
            'check_in' => '09:00',
            'check_out' => '17:00',
            'status' => AttendanceStatus::PRESENT->value,
            'source' => AttendanceSource::MANUAL->value,
        ],
    );

    $response->assertServerError();

    expect(
        Attendance::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('tenant_staff_id', $staff->id)
            ->whereDate(
                'attendance_date',
                '2026-09-07',
            )
            ->count(),
    )->toBe(1);
});

it('can list attendance records', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    Attendance::factory()
        ->count(3)
        ->create([
            'tenant_id' => $this->tenant->id,
            'tenant_staff_id' => $staff->id,
        ]);

    $response = $this->get(
        route('admin.hrm.attendances.index'),
    );

    $response->assertOk();
});

it('can search attendance by employee name', function (): void {
    $staffUser = User::factory()->create([
        'name' => 'Ram Bahadur',
    ]);

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    Attendance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->get(
        route('admin.hrm.attendances.index', [
            'search' => 'Ram Bahadur',
        ]),
    );

    $response->assertOk();
});

it('can filter attendance by status', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    Attendance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'status' => AttendanceStatus::LATE,
    ]);

    $response = $this->get(
        route('admin.hrm.attendances.index', [
            'status' => AttendanceStatus::LATE->value,
        ]),
    );

    $response->assertOk();
});

it('can filter attendance by date range', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    Attendance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'attendance_date' => '2026-09-05',
    ]);

    Attendance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'attendance_date' => '2026-09-10',
    ]);

    $response = $this->get(
        route('admin.hrm.attendances.index', [
            'from_date' => '2026-09-05',
            'to_date' => '2026-09-07',
        ]),
    );

    $response->assertOk();
});

it('can show attendance', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    $attendance = Attendance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->get(
        route(
            'admin.hrm.attendances.show',
            $attendance,
        ),
    );

    $response->assertOk();
});

it('can open attendance edit page', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    $attendance = Attendance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
    ]);

    $response = $this->get(
        route(
            'admin.hrm.attendances.edit',
            $attendance,
        ),
    );

    $response->assertOk();
});

it('can update attendance', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    $attendance = Attendance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'status' => AttendanceStatus::PRESENT,
    ]);

    $response = $this->put(
        route(
            'admin.hrm.attendances.update',
            $attendance,
        ),
        [
            'tenant_staff_id' => $staff->id,
            'attendance_date' => '2026-09-08',
            'check_in' => '09:30',
            'check_out' => '17:30',
            'late_minutes' => 30,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'status' => AttendanceStatus::LATE->value,
            'source' => AttendanceSource::WEB->value,
            'notes' => 'Late arrival',
            'is_active' => true,
        ],
    );

    $response->assertRedirect();

    $attendance->refresh();

    expect($attendance->attendance_date->toDateString())
        ->toBe('2026-09-08')
        ->and($attendance->worked_minutes)
        ->toBe(480)
        ->and($attendance->status)
        ->toBe(AttendanceStatus::LATE)
        ->and($attendance->updated_by)
        ->toBe($this->user->id);
});

it('can toggle attendance active status', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    $attendance = Attendance::factory()->create([
        'tenant_id' => $this->tenant->id,
        'tenant_staff_id' => $staff->id,
        'is_active' => true,
    ]);

    $response = $this->patch(
        route(
            'admin.hrm.attendances.toggle-status',
            $attendance,
        ),
    );

    $response->assertRedirect();

    expect(
        $attendance->refresh()->is_active,
    )->toBeFalse();
});

it('cannot access attendance from another tenant', function (): void {
    $otherTenant = Tenant::factory()->create();

    $otherUser = User::factory()->create();

    $otherStaff = createAttendanceStaff(
        $otherTenant,
        $otherUser,
    );

    $attendance = Attendance::factory()->create([
        'tenant_id' => $otherTenant->id,
        'tenant_staff_id' => $otherStaff->id,
    ]);

    $response = $this->get(
        route(
            'admin.hrm.attendances.show',
            $attendance,
        ),
    );

    $response->assertNotFound();
});

it('cannot create attendance for another tenant staff member', function (): void {
    $otherTenant = Tenant::factory()->create();

    $otherUser = User::factory()->create();

    $otherStaff = createAttendanceStaff(
        $otherTenant,
        $otherUser,
    );

    $response = $this->post(
        route('admin.hrm.attendances.store'),
        [
            'tenant_staff_id' => $otherStaff->id,
            'attendance_date' => '2026-09-07',
            'check_in' => '09:00',
            'check_out' => '17:00',
            'status' => AttendanceStatus::PRESENT->value,
            'source' => AttendanceSource::MANUAL->value,
        ],
    );

    $response->assertNotFound();

    expect(Attendance::count())->toBe(0);
});

it('cannot create attendance for a suspended employee', function (): void {
    $staffUser = User::factory()->create();

    $staff = TenantStaff::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $staffUser->id,
        'employment_status' => 'suspended',
    ]);

    $response = $this->post(
        route('admin.hrm.attendances.store'),
        [
            'tenant_staff_id' => $staff->id,
            'attendance_date' => '2026-09-07',
            'check_in' => '09:00',
            'check_out' => '17:00',
            'status' => AttendanceStatus::PRESENT->value,
            'source' => AttendanceSource::MANUAL->value,
        ],
    );

    $response->assertNotFound();
});

it('validates attendance date', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    $response = $this->post(
        route('admin.hrm.attendances.store'),
        [
            'tenant_staff_id' => $staff->id,
            'attendance_date' => 'invalid-date',
            'status' => AttendanceStatus::PRESENT->value,
            'source' => AttendanceSource::MANUAL->value,
        ],
    );

    $response->assertSessionHasErrors(
        'attendance_date',
    );
});

it('validates check in and check out format', function (): void {
    $staffUser = User::factory()->create();

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    $response = $this->post(
        route('admin.hrm.attendances.store'),
        [
            'tenant_staff_id' => $staff->id,
            'attendance_date' => '2026-09-07',
            'check_in' => 'invalid',
            'check_out' => 'invalid',
            'status' => AttendanceStatus::PRESENT->value,
            'source' => AttendanceSource::MANUAL->value,
        ],
    );

    $response->assertSessionHasErrors([
        'check_in',
        'check_out',
    ]);
});

it('requires attendance view permission', function (): void {
    $userWithoutPermission = User::factory()->create();
    $restrictedRole = TenantRole::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Restricted',
        'slug' => 'restricted',
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
        route('admin.hrm.attendances.index'),
    );

    $response->assertForbidden();
});

it('requires attendance manage permission', function (): void {
    $userWithoutPermission = User::factory()->create();
    $restrictedRole = TenantRole::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Restricted Manage',
        'slug' => 'restricted-manage',
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

    $staff = createAttendanceStaff(
        $this->tenant,
        $staffUser,
    );

    $response = $this->post(
        route('admin.hrm.attendances.store'),
        [
            'tenant_staff_id' => $staff->id,
            'attendance_date' => '2026-09-07',
            'check_in' => '09:00',
            'check_out' => '17:00',
            'status' => AttendanceStatus::PRESENT->value,
            'source' => AttendanceSource::MANUAL->value,
        ],
    );

    $response->assertForbidden();
});
