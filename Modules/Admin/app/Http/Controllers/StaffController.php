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

    public function create(): Response
    {
        $tenantId = $this->currentTenant->id();

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

        return Inertia::render('Admin/Staff/Create', [
            'branches' => $branches,
            'departments' => $departments,
            'designations' => $designations,
        ]);
    }

    public function store(
        StoreStaffRequest $request,
        CreateStaffAction $action,
    ): RedirectResponse {
        $data = new CreateStaffData(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            phone: $request->input('phone'),
            employeeCode: $request->string('employee_code')->toString(),
            branchId: $request->integer('branch_id'),
            departmentId: $request->integer('department_id'),
            designationId: $request->integer('designation_id'),
            joiningDate: $request->filled('joining_date')
                ? CarbonImmutable::parse($request->input('joining_date'))
                : null,
        );

        $action->execute($data);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function edit(TenantStaff $staff): Response
    {
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
        $this->authorizeTenantStaff($staff);

        $action->execute($staff);

        return back()->with('success', 'Staff member activated successfully.');
    }

    public function suspend(
        TenantStaff $staff,
        SuspendStaffAction $action,
    ): RedirectResponse {
        $this->authorizeTenantStaff($staff);

        $action->execute($staff);

        return back()->with('success', 'Staff member suspended successfully.');
    }

    public function destroy(
        TenantStaff $staff,
        SuspendStaffAction $action,
    ): RedirectResponse {
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
