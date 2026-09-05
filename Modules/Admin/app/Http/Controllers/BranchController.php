<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admin\Http\Requests\StoreBranchRequest;
use Modules\Admin\Http\Requests\UpdateBranchRequest;
use Modules\Tenancy\Actions\ActivateBranchAction;
use Modules\Tenancy\Actions\CreateBranchAction;
use Modules\Tenancy\Actions\DeactivateBranchAction;
use Modules\Tenancy\Actions\UpdateBranchAction;
use Modules\Tenancy\Data\CreateBranchData;
use Modules\Tenancy\Data\UpdateBranchData;
use Modules\Tenancy\Domain\Enums\BranchStatus;
use Modules\Tenancy\Models\Branch;

class BranchController extends Controller
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

        $branches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, [BranchStatus::Active->value, BranchStatus::Inactive->value], true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Branches/Index', [
            'branches' => $branches,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Branches/Create');
    }

    public function store(
        StoreBranchRequest $request,
        CreateBranchAction $action,
    ): RedirectResponse {
        $data = new CreateBranchData(
            name: $request->validated('name'),
            code: $request->validated('code'),
            addressLine1: $request->validated('address_line_1'),
            addressLine2: $request->validated('address_line_2'),
            city: $request->validated('city'),
            state: $request->validated('state'),
            postalCode: $request->validated('postal_code'),
            countryCode: $request->validated('country_code'),
        );

        $branch = $action->handle($data);

        return redirect()->route('admin.branches.index')
            ->with('success', "Branch '{$branch->name}' created successfully.");
    }

    public function show(Branch $branch): Response
    {
        $this->authorizeTenantBranch($branch);

        return Inertia::render('Admin/Branches/Show', [
            'branch' => $branch,
        ]);
    }

    public function edit(Branch $branch): Response
    {
        $this->authorizeTenantBranch($branch);

        return Inertia::render('Admin/Branches/Edit', [
            'branch' => $branch,
        ]);
    }

    public function update(
        UpdateBranchRequest $request,
        Branch $branch,
        UpdateBranchAction $action,
    ): RedirectResponse {
        $this->authorizeTenantBranch($branch);

        $data = new UpdateBranchData(
            name: $request->validated('name'),
            code: $request->validated('code'),
            status: BranchStatus::from($request->validated('status')),
            addressLine1: $request->validated('address_line_1'),
            addressLine2: $request->validated('address_line_2'),
            city: $request->validated('city'),
            state: $request->validated('state'),
            postalCode: $request->validated('postal_code'),
            countryCode: $request->validated('country_code'),
        );

        $branch = $action->handle($branch, $data);

        return redirect()->route('admin.branches.index')
            ->with('success', "Branch '{$branch->name}' updated successfully.");
    }

    public function activate(
        Branch $branch,
        ActivateBranchAction $action,
    ): RedirectResponse {
        $this->authorizeTenantBranch($branch);

        $action->handle($branch);

        return back()->with('success', "Branch '{$branch->name}' activated successfully.");
    }

    public function deactivate(
        Branch $branch,
        DeactivateBranchAction $action,
    ): RedirectResponse {
        $this->authorizeTenantBranch($branch);

        $action->handle($branch);

        return back()->with('success', "Branch '{$branch->name}' deactivated successfully.");
    }

    public function destroy(
        Branch $branch,
        DeactivateBranchAction $action,
    ): RedirectResponse {
        $this->authorizeTenantBranch($branch);

        $action->handle($branch);

        return redirect()->route('admin.branches.index')
            ->with('success', "Branch '{$branch->name}' deactivated successfully.");
    }

    private function authorizeTenantBranch(Branch $branch): void
    {
        if ($branch->tenant_id !== $this->currentTenant->id()) {
            abort(404);
        }
    }
}
