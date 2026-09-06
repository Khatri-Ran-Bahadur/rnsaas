<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admin\Http\Requests\StoreDesignationRequest;
use Modules\Admin\Http\Requests\UpdateDesignationRequest;
use Modules\Tenancy\Application\Actions\Organization\ActivateDesignationAction;
use Modules\Tenancy\Application\Actions\Organization\CreateDesignationAction;
use Modules\Tenancy\Application\Actions\Organization\DeactivateDesignationAction;
use Modules\Tenancy\Application\Actions\Organization\UpdateDesignationAction;
use Modules\Tenancy\Application\DTOs\CreateDesignationData;
use Modules\Tenancy\Application\DTOs\UpdateDesignationData;
use Modules\Tenancy\Domain\Enums\DesignationStatus;
use Modules\Tenancy\Models\Designation;

class DesignationController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('designations.view');

        $tenantId = $this->currentTenant->id();

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $perPage = $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $designations = Designation::query()
            ->forTenant($tenantId)
            ->withCount('staff')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, [DesignationStatus::Active->value, DesignationStatus::Inactive->value], true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Designations/Index', [
            'designations' => $designations,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(
        StoreDesignationRequest $request,
        CreateDesignationAction $action,
    ): RedirectResponse {
        $this->authorize('designations.manage');

        $data = new CreateDesignationData(
            name: $request->string('name')->toString(),
            code: $request->string('code')->toString(),
        );

        $designation = $action->execute($data);

        return redirect()->route('admin.designations.index')
            ->with('success', "Designation '{$designation->name}' created successfully.");
    }

    public function update(
        UpdateDesignationRequest $request,
        Designation $designation,
        UpdateDesignationAction $action,
    ): RedirectResponse {
        $this->authorize('designations.manage');
        $this->authorizeTenantDesignation($designation);

        $data = new UpdateDesignationData(
            name: $request->string('name')->toString(),
            code: $request->string('code')->toString(),
        );

        $action->execute($designation, $data);

        return redirect()->route('admin.designations.index')
            ->with('success', "Designation '{$designation->name}' updated successfully.");
    }

    public function activate(
        Designation $designation,
        ActivateDesignationAction $action,
    ): RedirectResponse {
        $this->authorize('designations.manage');
        $this->authorizeTenantDesignation($designation);

        $action->execute($designation);

        return back()->with('success', "Designation '{$designation->name}' activated successfully.");
    }

    public function deactivate(
        Designation $designation,
        DeactivateDesignationAction $action,
    ): RedirectResponse {
        $this->authorize('designations.manage');
        $this->authorizeTenantDesignation($designation);

        $action->execute($designation);

        return back()->with('success', "Designation '{$designation->name}' deactivated successfully.");
    }

    public function destroy(
        Designation $designation,
        DeactivateDesignationAction $action,
    ): RedirectResponse {
        $this->authorize('designations.manage');
        $this->authorizeTenantDesignation($designation);

        $action->execute($designation);

        return redirect()->route('admin.designations.index')
            ->with('success', "Designation '{$designation->name}' deactivated successfully.");
    }

    private function authorizeTenantDesignation(Designation $designation): void
    {
        if ($designation->tenant_id !== $this->currentTenant->id()) {
            abort(404);
        }
    }
}
