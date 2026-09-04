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
}
