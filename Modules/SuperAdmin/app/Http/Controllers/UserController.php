<?php

namespace Modules\SuperAdmin\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with([
                'roles:id,name',
            ])
            ->withCount('tenants')
            ->when(
                $request->filled('search'),
                function ($query) use ($request): void {
                    $search = $request->string('search')->trim()->value();

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                },
            )
            ->when(
                $request->filled('role'),
                function ($query) use ($request): void {
                    $role = $request->string('role')->trim()->value();

                    $query->whereHas(
                        'roles',
                        fn ($roleQuery) => $roleQuery->where('name', $role),
                    );
                },
            )
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::query()
            ->select(['id', 'name'])
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return Inertia::render('SuperAdmin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $request->string('search')->trim()->value(),
                'role' => $request->string('role')->trim()->value(),
            ],
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $user->load([
            'roles:id,name',
            'tenants',
        ]);

        return Inertia::render('SuperAdmin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => null,
                'account_status' => 'active',
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'created_at' => $user->created_at->toISOString(),
                'last_login_at' => null,
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values()->all(),
                'organizations' => $user->tenants->map(fn ($tenant) => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug ?? null,
                    'role' => 'Member',
                    'status' => $tenant->pivot->status ?? 'active',
                    'joined_at' => $tenant->pivot->joined_at ?? $tenant->pivot->created_at?->toISOString(),
                ])->values()->all(),
                'sessions' => [],
                'password_set' => true,
            ],
        ]);
    }
}
