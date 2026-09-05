<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tenancy\Application\Actions\Staff\CreateStaffAction;
use Modules\Tenancy\Application\DTOs\CreateStaffData;
use Modules\Tenancy\Http\Requests\StoreStaffRequest;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\TenantStaff;

class StaffController extends Controller
{
    public function index(): Response
    {
        $staff = TenantStaff::query()
            ->with([
                'user:id,name,email,phone',
                'branch:id,public_id,name,code',
            ])
            ->where(
                'tenant_id',
                app(CurrentTenant::class)->id()
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
        ]);
    }

    public function create(): Response
    {
        $tenantId = app(
            CurrentTenant::class
        )->id();

        $branches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get([
                'id',
                'public_id',
                'name',
                'code',
            ]);

        return Inertia::render('Admin/Staff/Create', [
            'branches' => $branches,
        ]);
    }

    public function store(
        StoreStaffRequest $request,
        CreateStaffAction $action,
    ) {
        $validated = $request->validated();

        $staff = $action->handle(
            new CreateStaffData(
                name: $validated['name'],
                email: $validated['email'],
                phone: $validated['phone'] ?? null,
                employeeCode: $validated['employee_code'],
                designation: $validated['designation'],
                baseSalary: $validated['base_salary'] ?? null,
                joiningDate: isset($validated['joining_date'])
                    ? CarbonImmutable::parse(
                        $validated['joining_date']
                    )
                    : null,
                branchId: $validated['branch_id'],
            )
        );

        return to_route('admin.staff.show', $staff);
    }
}
