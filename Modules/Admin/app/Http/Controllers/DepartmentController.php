<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admin\Http\Requests\StoreDepartmentRequest;
use Modules\Admin\Http\Requests\UpdateDepartmentRequest;
use Modules\Tenancy\Application\Actions\Organization\ActivateDepartmentAction;
use Modules\Tenancy\Application\Actions\Organization\CreateDepartmentAction;
use Modules\Tenancy\Application\Actions\Organization\DeactivateDepartmentAction;
use Modules\Tenancy\Application\Actions\Organization\UpdateDepartmentAction;
use Modules\Tenancy\Application\DTOs\CreateDepartmentData;
use Modules\Tenancy\Application\DTOs\UpdateDepartmentData;
use Modules\Tenancy\Domain\Enums\DepartmentStatus;
use Modules\Tenancy\Models\Department;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = $this->currentTenant->id();

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $perPage = $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $departments = Department::query()
            ->forTenant($tenantId)
            ->withCount('staff')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, [DepartmentStatus::Active->value, DepartmentStatus::Inactive->value], true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(
        StoreDepartmentRequest $request,
        CreateDepartmentAction $action,
    ): RedirectResponse {
        $data = new CreateDepartmentData(
            name: $request->string('name')->toString(),
            code: $request->string('code')->toString(),
        );

        $department = $action->execute($data);

        return redirect()->route('admin.departments.index')
            ->with('success', "Department '{$department->name}' created successfully.");
    }

    public function update(
        UpdateDepartmentRequest $request,
        Department $department,
        UpdateDepartmentAction $action,
    ): RedirectResponse {
        $this->authorizeTenantDepartment($department);

        $data = new UpdateDepartmentData(
            name: $request->string('name')->toString(),
            code: $request->string('code')->toString(),
        );

        $action->execute($department, $data);

        return redirect()->route('admin.departments.index')
            ->with('success', "Department '{$department->name}' updated successfully.");
    }

    public function activate(
        Department $department,
        ActivateDepartmentAction $action,
    ): RedirectResponse {
        $this->authorizeTenantDepartment($department);

        $action->execute($department);

        return back()->with('success', "Department '{$department->name}' activated successfully.");
    }

    public function deactivate(
        Department $department,
        DeactivateDepartmentAction $action,
    ): RedirectResponse {
        $this->authorizeTenantDepartment($department);

        $action->execute($department);

        return back()->with('success', "Department '{$department->name}' deactivated successfully.");
    }

    public function destroy(
        Department $department,
        DeactivateDepartmentAction $action,
    ): RedirectResponse {
        $this->authorizeTenantDepartment($department);

        $action->execute($department);

        return redirect()->route('admin.departments.index')
            ->with('success', "Department '{$department->name}' deactivated successfully.");
    }

    private function authorizeTenantDepartment(Department $department): void
    {
        if ($department->tenant_id !== $this->currentTenant->id()) {
            abort(404);
        }
    }
}
