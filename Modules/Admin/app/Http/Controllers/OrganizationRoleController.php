<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;
use Modules\Admin\Http\Requests\StoreTenantRoleRequest;
use Modules\Admin\Http\Requests\UpdateTenantRoleRequest;
use Modules\Tenancy\Application\Actions\Role\CreateTenantRoleAction;
use Modules\Tenancy\Application\Actions\Role\DeleteTenantRoleAction;
use Modules\Tenancy\Application\Actions\Role\UpdateTenantRoleAction;
use Modules\Tenancy\Domain\Constants\OrganizationPermissions;
use Modules\Tenancy\Models\TenantRole;

class OrganizationRoleController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('roles.view');

        $tenantId = $this->currentTenant->id();
        $search = $request->string('search')->trim()->value();

        $roles = TenantRole::query()
            ->where('tenant_id', $tenantId)
            ->withCount('members')
            ->with('permissions')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('roles.manage');

        return Inertia::render('Admin/Roles/Create', [
            'permission_groups' => OrganizationPermissions::structured(),
        ]);
    }

    public function store(
        StoreTenantRoleRequest $request,
        CreateTenantRoleAction $action,
    ): RedirectResponse {
        $this->authorize('roles.manage');

        $tenant = $this->currentTenant->get();

        $role = $action->execute(
            tenant: $tenant,
            name: $request->validated('name'),
            description: $request->validated('description'),
            permissions: $request->validated('permissions', []),
            isActive: $request->boolean('is_active', true),
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' created successfully.");
    }

    public function edit(int $id): Response
    {
        $this->authorize('roles.manage');

        $tenantId = $this->currentTenant->id();
        $role = TenantRole::query()
            ->where('tenant_id', $tenantId)
            ->with('permissions')
            ->findOrFail($id);

        return Inertia::render('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'is_active' => $role->is_active,
                'permissions' => $role->permissions->pluck('permission')->toArray(),
            ],
            'permission_groups' => OrganizationPermissions::structured(),
        ]);
    }

    public function update(
        UpdateTenantRoleRequest $request,
        int $id,
        UpdateTenantRoleAction $action,
    ): RedirectResponse {
        $this->authorize('roles.manage');

        $tenantId = $this->currentTenant->id();
        $role = TenantRole::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $action->execute(
            role: $role,
            name: $request->validated('name'),
            description: $request->validated('description'),
            permissions: $request->validated('permissions'),
            isActive: $request->boolean('is_active', true),
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function destroy(
        int $id,
        DeleteTenantRoleAction $action,
    ): RedirectResponse {
        $this->authorize('roles.manage');

        $tenantId = $this->currentTenant->id();
        $role = TenantRole::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        try {
            $action->execute($role);
        } catch (LogicException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.roles.index')
            ->with('success', "Role '{$role->name}' deleted successfully.");
    }
}
