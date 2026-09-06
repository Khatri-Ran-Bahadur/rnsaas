<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admin\Http\Requests\StoreStaffRequest;
use Modules\Admin\Http\Requests\UpdateStaffRequest;
use Modules\Tenancy\Application\Actions\Staff\ActivateStaffAction;
use Modules\Tenancy\Application\Actions\Staff\CreateStaffAction;
use Modules\Tenancy\Application\Actions\Staff\SuspendStaffAction;
use Modules\Tenancy\Application\Actions\Staff\UpdateStaffAction;
use Modules\Tenancy\Application\DTOs\CreateStaffData;
use Modules\Tenancy\Application\DTOs\UpdateStaffData;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\TenantStaff;

class StaffController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('staff.view');

        $tenantId = $this->currentTenant->id();

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $branchId = $request->integer('branch_id');
        $departmentId = $request->integer('department_id');
        $designationId = $request->integer('designation_id');
        $perPage = $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $staff = TenantStaff::query()
            ->forTenant($tenantId)
            ->with(['user', 'branch', 'department', 'designation'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('employee_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($status, [EmploymentStatus::Active->value, EmploymentStatus::Suspended->value, EmploymentStatus::Terminated->value], true), function (Builder $query) use ($status): void {
                $query->where('employment_status', $status);
            })
            ->when($branchId > 0, function (Builder $query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->when($departmentId > 0, function (Builder $query) use ($departmentId): void {
                $query->where('department_id', $departmentId);
            })
            ->when($designationId > 0, function (Builder $query) use ($designationId): void {
                $query->where('designation_id', $designationId);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $branches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $departments = Department::query()
            ->forTenant($tenantId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $designations = Designation::query()
            ->forTenant($tenantId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
            'branches' => $branches,
            'departments' => $departments,
            'designations' => $designations,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'branch_id' => $branchId ?: null,
                'department_id' => $departmentId ?: null,
                'designation_id' => $designationId ?: null,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('staff.manage');

        $tenant = $this->currentTenant->get();
        $tenantId = $tenant->id;

        $preselectedUserId = $request->integer('user_id') ?: null;
        if (! $preselectedUserId && $request->has('member_id')) {
            $member = TenantMembership::query()
                ->where('tenant_id', $tenantId)
                ->find($request->integer('member_id'));
            $preselectedUserId = $member?->user_id;
        }

        $branches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $departments = Department::query()
            ->forTenant($tenantId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $designations = Designation::query()
            ->forTenant($tenantId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $availableMembers = $tenant->users()
            ->wherePivot('status', TenantMembershipStatus::Active->value)
            ->whereNotIn('users.id', function ($query) use ($tenantId): void {
                $query->select('user_id')
                    ->from('tenant_staff')
                    ->where('tenant_id', $tenantId);
            })
            ->select(['users.id', 'users.name', 'users.email'])
            ->orderBy('users.name')
            ->get();

        return Inertia::render('Admin/Staff/Create', [
            'branches' => $branches,
            'departments' => $departments,
            'designations' => $designations,
            'availableMembers' => $availableMembers,
            'preselectedUserId' => $preselectedUserId,
        ]);
    }

    public function store(
        StoreStaffRequest $request,
        CreateStaffAction $action,
    ): RedirectResponse {
        $this->authorize('staff.manage');

        $userId = $request->filled('user_id') ? $request->integer('user_id') : null;
        $employmentStatus = $request->filled('employment_status')
            ? EmploymentStatus::from($request->string('employment_status')->toString())
            : EmploymentStatus::Active;

        $data = new CreateStaffData(
            userId: $userId,
            name: $request->filled('name') ? $request->string('name')->toString() : null,
            email: $request->filled('email') ? $request->string('email')->toString() : null,
            phone: $request->input('phone'),
            employeeCode: $request->string('employee_code')->toString(),
            branchId: $request->integer('branch_id'),
            departmentId: $request->integer('department_id'),
            designationId: $request->integer('designation_id'),
            joiningDate: $request->filled('joining_date')
                ? CarbonImmutable::parse($request->input('joining_date'))
                : null,
            employmentStatus: $employmentStatus,
        );

        $action->execute($data);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function edit(TenantStaff $staff): Response
    {
        $this->authorize('staff.manage');
        $this->authorizeTenantStaff($staff);

        $staff->load(['user', 'branch', 'department', 'designation']);

        $tenantId = $this->currentTenant->id();

        $branches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $departments = Department::query()
            ->forTenant($tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $designations = Designation::query()
            ->forTenant($tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Staff/Edit', [
            'staff' => $staff,
            'branches' => $branches,
            'departments' => $departments,
            'designations' => $designations,
        ]);
    }

    public function update(
        UpdateStaffRequest $request,
        TenantStaff $staff,
        UpdateStaffAction $action,
    ): RedirectResponse {
        $this->authorize('staff.manage');
        $this->authorizeTenantStaff($staff);

        $data = new UpdateStaffData(
            name: $request->string('name')->toString(),
            phone: $request->input('phone'),
            employeeCode: $request->string('employee_code')->toString(),
            branchId: $request->integer('branch_id'),
            departmentId: $request->integer('department_id'),
            designationId: $request->integer('designation_id'),
            joiningDate: $request->filled('joining_date')
                ? CarbonImmutable::parse($request->input('joining_date'))
                : null,
            employmentStatus: EmploymentStatus::from($request->string('employment_status')->toString()),
        );

        $action->execute($staff, $data);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    public function activate(
        TenantStaff $staff,
        ActivateStaffAction $action,
    ): RedirectResponse {
        $this->authorize('staff.manage');
        $this->authorizeTenantStaff($staff);

        $action->execute($staff);

        return back()->with('success', 'Staff member activated successfully.');
    }

    public function suspend(
        TenantStaff $staff,
        SuspendStaffAction $action,
    ): RedirectResponse {
        $this->authorize('staff.manage');
        $this->authorizeTenantStaff($staff);

        $action->execute($staff);

        return back()->with('success', 'Staff member suspended successfully.');
    }

    public function destroy(
        TenantStaff $staff,
        SuspendStaffAction $action,
    ): RedirectResponse {
        $this->authorize('staff.manage');
        $this->authorizeTenantStaff($staff);

        $action->execute($staff);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member suspended successfully.');
    }

    private function authorizeTenantStaff(TenantStaff $staff): void
    {
        if ($staff->tenant_id !== $this->currentTenant->id()) {
            abort(404);
        }
    }
}
