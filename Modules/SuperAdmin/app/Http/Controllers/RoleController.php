<?php

namespace Modules\SuperAdmin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SuperAdmin\Actions\Roles\CreatePlatformRoleAction;
use Modules\SuperAdmin\Actions\Roles\DeletePlatformRoleAction;
use Modules\SuperAdmin\Actions\Roles\UpdatePlatformRoleAction;
use Modules\SuperAdmin\Http\Requests\StoreRoleRequest;
use Modules\SuperAdmin\Http\Requests\UpdateRoleRequest;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController
{
    public function index(): Response
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->withCount([
                'users',
                'permissions',
            ])
            ->with([
                'permissions:id,name',
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('Roles/Index', [
            'roles' => $roles->map(
                fn (Role $role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'users_count' => $role->users_count,
                    'permissions_count' => $role->permissions_count,
                    'is_system' => $role->name === 'SuperAdmin',
                    'permissions' => $role->permissions
                        ->pluck('name')
                        ->values()
                        ->all(),
                ],
            )->values()->all(),
            'permission_groups' => $this->permissionGroups(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Create', [
            'permission_groups' => $this->permissionGroups(),
        ]);
    }

    public function store(
        StoreRoleRequest $request,
        CreatePlatformRoleAction $action,
    ): RedirectResponse {
        $action->execute(
            name: $request->string('name')->trim()->value(),
            permissionNames: $request->input('permissions', []),
            actor: $request->user(),
        );

        $route = $request->routeIs('admin.*') ? 'admin.roles.index' : 'superadmin.roles.index';

        return redirect()->route($route)->with(
            'success',
            'Platform role created successfully.',
        );
    }

    public function edit(Request $request, Role $role): Response|RedirectResponse
    {
        if ($role->name === 'SuperAdmin') {
            $route = $request->routeIs('admin.*') ? 'admin.roles.index' : 'superadmin.roles.index';

            return redirect()->route($route)->with(
                'error',
                'The SuperAdmin role is protected and cannot be edited.',
            );
        }

        $role->load(['permissions:id,name']);

        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'is_system' => $role->name === 'SuperAdmin',
                'permissions' => $role->permissions
                    ->pluck('name')
                    ->values()
                    ->all(),
            ],
            'permission_groups' => $this->permissionGroups(),
        ]);
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role,
        UpdatePlatformRoleAction $action,
    ): RedirectResponse {
        $action->execute(
            role: $role,
            name: $request->string('name')->trim()->value(),
            permissionNames: $request->input('permissions', []),
            actor: $request->user(),
        );

        $route = $request->routeIs('admin.*') ? 'admin.roles.index' : 'superadmin.roles.index';

        return redirect()->route($route)->with(
            'success',
            'Platform role updated successfully.',
        );
    }

    public function destroy(
        Role $role,
        DeletePlatformRoleAction $action,
    ): RedirectResponse {
        $action->execute(
            role: $role,
            actor: request()->user(),
        );

        return back()->with(
            'success',
            'Platform role deleted successfully.',
        );
    }

    /**
     * @return array<int, array{key: string, label: string, permissions: array<int, array{id: int, name: string, label: string}>, sub_groups: array<int, array{key: string, label: string, permissions: array<int, array{id: int, name: string, label: string}>}>}>
     */
    private function permissionGroups(): array
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->select([
                'id',
                'name',
            ])
            ->orderBy('name')
            ->get();

        $moduleMap = [
            'dashboard' => 'General',
            'users' => 'General',
            'roles' => 'General',
            'settings' => 'General',
            'audit' => 'General',
            'tenants' => 'Tenancy',
            'members' => 'Tenancy',
            'subscriptions' => 'Subscription',
            'payments' => 'Payment',
            'media' => 'Media',
        ];

        $formattedPermissions = $permissions->map(function (Permission $permission) use ($moduleMap): array {
            $name = $permission->name;
            $parts = explode('.', $name);

            $prefix = $parts[0];
            $hasSubPart = count($parts) > 2;
            $subResource = $hasSubPart ? $parts[1] : $prefix;
            $action = $hasSubPart ? implode(' ', array_slice($parts, 2)) : ($parts[1] ?? 'access');

            $actionLabel = Str::headline($action);
            $resourceLabel = Str::headline($subResource);

            $label = match ($name) {
                'media.manage-any' => 'Manage Any Media',
                'media.manage-own' => 'Manage Own Media',
                'media.directories.create' => 'Create Directories',
                'media.directories.update' => 'Edit Directories',
                'media.directories.delete' => 'Delete Directories',
                'settings.cache.clear' => 'Clear Cache',
                default => "{$actionLabel} {$resourceLabel}",
            };

            $moduleName = $moduleMap[$prefix] ?? Str::headline($prefix);
            $subGroupKey = $hasSubPart ? "{$prefix}-{$parts[1]}" : $prefix;
            $subGroupLabel = $hasSubPart ? Str::headline($parts[1]) : Str::headline($prefix);

            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => $label,
                'module_key' => Str::slug($moduleName),
                'module_label' => $moduleName,
                'sub_group_key' => $subGroupKey,
                'sub_group_label' => $subGroupLabel,
            ];
        });

        return $formattedPermissions
            ->groupBy('module_key')
            ->map(function ($moduleItems, string $moduleKey): array {
                $moduleLabel = $moduleItems->first()['module_label'];

                $subGroups = $moduleItems
                    ->groupBy('sub_group_key')
                    ->map(function ($subItems, string $subGroupKey): array {
                        return [
                            'key' => $subGroupKey,
                            'label' => $subItems->first()['sub_group_label'],
                            'permissions' => $subItems->map(fn ($item): array => [
                                'id' => $item['id'],
                                'name' => $item['name'],
                                'label' => $item['label'],
                            ])->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();

                $allModulePermissions = $moduleItems->map(fn ($item): array => [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'label' => $item['label'],
                ])->values()->all();

                return [
                    'key' => $moduleKey,
                    'label' => $moduleLabel,
                    'permissions' => $allModulePermissions,
                    'sub_groups' => $subGroups,
                ];
            })
            ->values()
            ->all();
    }
}
