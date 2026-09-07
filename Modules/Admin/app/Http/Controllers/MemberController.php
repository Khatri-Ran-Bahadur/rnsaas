<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admin\Http\Requests\StoreMemberRequest;
use Modules\Admin\Http\Requests\UpdateMemberRequest;
use Modules\Tenancy\Application\Actions\Membership\InviteTenantMemberAction;
use Modules\Tenancy\Application\Actions\Membership\ReactivateTenantMemberAction;
use Modules\Tenancy\Application\Actions\Membership\RevokeTenantMemberAction;
use Modules\Tenancy\Application\Actions\Membership\SuspendTenantMemberAction;
use Modules\Tenancy\Application\Actions\Membership\UpdateTenantMemberAction;
use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;

class MemberController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('members.view');

        $tenantId = $this->currentTenant->id();

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $roleId = $request->integer('role_id') ?: null;
        $staffFilter = $request->string('is_staff')->trim()->value();
        $perPage = $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $members = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'user:id,name,email',
                'role:id,name,slug,is_system',
                'staff' => fn ($query) => $query->where('tenant_id', $tenantId)->select(['id', 'user_id', 'tenant_id', 'employee_code', 'employment_status']),
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('user', function (Builder $userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($roleId !== null, function (Builder $query) use ($roleId): void {
                $query->where('role_id', $roleId);
            })
            ->when($staffFilter !== '', function (Builder $query) use ($staffFilter, $tenantId): void {
                if ($staffFilter === 'yes') {
                    $query->whereHas('staff', fn (Builder $sq) => $sq->where('tenant_id', $tenantId));
                } elseif ($staffFilter === 'no') {
                    $query->whereDoesntHave('staff', fn (Builder $sq) => $sq->where('tenant_id', $tenantId));
                }
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $roles = TenantRole::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_system']);

        return Inertia::render('Admin/Members/Index', [
            'members' => $members,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'role_id' => $roleId,
                'is_staff' => $staffFilter,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(
        StoreMemberRequest $request,
        InviteTenantMemberAction $inviteAction,
    ): RedirectResponse {
        $this->authorize('members.invite');

        $tenant = $this->currentTenant->get();
        $email = $request->validated('email');
        $name = $request->validated('name');
        $roleId = $request->validated('role_id');
        $status = $request->validated('status', 'active');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name ?? explode('@', $email)[0],
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        if ($status === 'invited') {
            $inviteAction->execute($tenant, $user, $request->user(), $roleId);
            $message = "Invitation sent to {$user->email} successfully.";
        } else {
            $membership = TenantMembership::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $membership) {
                TenantMembership::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                    'status' => TenantMembershipStatus::Active,
                    'joined_at' => now(),
                    'settings' => [],
                    'version' => 1,
                ]);
            } else {
                $membership->update([
                    'role_id' => $roleId,
                    'status' => TenantMembershipStatus::Active,
                    'joined_at' => $membership->joined_at ?? now(),
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'version' => $membership->version + 1,
                ]);
            }
            $message = "Member {$user->name} added successfully.";
        }

        return redirect()->route('admin.members.index')->with('success', $message);
    }

    public function show(int $id): Response
    {
        $this->authorize('members.view');

        $tenantId = $this->currentTenant->id();

        $member = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with([
                'user:id,name,email,created_at',
                'role.permissions',
                'invitedBy:id,name,email',
                'suspendedBy:id,name,email',
                'revokedBy:id,name,email',
                'staff' => fn ($query) => $query->where('tenant_id', $tenantId)
                    ->with(['branch:id,name,code', 'department:id,name,code', 'designation:id,name,code']),
            ])
            ->firstOrFail();

        $roles = TenantRole::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_system']);

        return Inertia::render('Admin/Members/Show', [
            'member' => $member,
            'roles' => $roles,
        ]);
    }

    public function update(
        UpdateMemberRequest $request,
        int $id,
        UpdateTenantMemberAction $action,
    ): RedirectResponse {
        $this->authorize('members.manage');

        $tenantId = $this->currentTenant->id();
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with(['role'])
            ->firstOrFail();

        $authService = app(OrganizationAuthorizationService::class);
        $actor = $request->user();

        if ($membership->user_id === $actor->id && $request->integer('role_id') !== $membership->role_id) {
            return redirect()->back()->with('error', 'You cannot change your own organization role.');
        }

        if ($membership->role?->slug === 'admin' && ! $authService->isAdmin($actor, $tenantId)) {
            return redirect()->back()->with('error', 'Only an organization administrator can modify an admin member.');
        }

        $action->execute(
            $membership,
            $request->integer('role_id'),
            $request->validated('status'),
            $actor,
        );

        return redirect()->back()->with('success', 'Member details updated successfully.');
    }

    public function suspend(
        Request $request,
        int $id,
        SuspendTenantMemberAction $action,
    ): RedirectResponse {
        $this->authorize('members.manage');

        $tenantId = $this->currentTenant->id();
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with(['role'])
            ->firstOrFail();

        if ($membership->user_id === $request->user()->id) {
            return redirect()->back()->with('error', 'You cannot suspend your own membership.');
        }

        if ($membership->role?->slug === 'admin' && ! app(OrganizationAuthorizationService::class)->isAdmin($request->user(), $tenantId)) {
            return redirect()->back()->with('error', 'Only an organization administrator can suspend an admin member.');
        }

        $action->execute($membership, $request->user());

        return redirect()->back()->with('success', 'Member access suspended.');
    }

    public function reactivate(
        Request $request,
        int $id,
        ReactivateTenantMemberAction $action,
    ): RedirectResponse {
        $this->authorize('members.manage');

        $tenantId = $this->currentTenant->id();
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $action->execute($membership, $request->user());

        return redirect()->back()->with('success', 'Member access reactivated.');
    }

    public function revoke(
        Request $request,
        int $id,
        RevokeTenantMemberAction $action,
    ): RedirectResponse {
        $this->authorize('members.manage');

        $tenantId = $this->currentTenant->id();
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with(['role'])
            ->firstOrFail();

        if ($membership->user_id === $request->user()->id) {
            return redirect()->back()->with('error', 'You cannot revoke your own membership.');
        }

        if ($membership->role?->slug === 'admin' && ! app(OrganizationAuthorizationService::class)->isAdmin($request->user(), $tenantId)) {
            return redirect()->back()->with('error', 'Only an organization administrator can revoke an admin member.');
        }

        $action->execute($membership, $request->user());

        return redirect()->back()->with('success', 'Member access revoked.');
    }
}
