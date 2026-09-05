<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Tenancy\Application\Actions\Membership\InviteTenantMemberAction;
use Modules\Tenancy\Application\Actions\Membership\ReactivateTenantMemberAction;
use Modules\Tenancy\Application\Actions\Membership\RevokeTenantMemberAction;
use Modules\Tenancy\Application\Actions\Membership\SuspendTenantMemberAction;
use Modules\Tenancy\Http\Requests\InviteTenantMemberRequest;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

class TenantMemberController
{
    /**
     * Invite a new or existing user to the tenant organization.
     */
    public function invite(
        InviteTenantMemberRequest $request,
        Tenant $tenant,
        InviteTenantMemberAction $action
    ): RedirectResponse {
        $email = strtolower(trim($request->validated('email')));
        $name = trim($request->validated('name') ?? '');

        if ($name === '') {
            $name = explode('@', $email)[0];
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(Str::random(32)),
            ]
        );

        $action->execute($tenant, $user, $request->user());

        return back()->with(
            'success',
            "Invitation sent to {$user->email}.",
        );
    }

    /**
     * Suspend a member's access in this tenant.
     */
    public function suspend(
        Request $request,
        Tenant $tenant,
        User $user,
        SuspendTenantMemberAction $action
    ): RedirectResponse {
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $action->execute(
            $membership,
            $request->user(),
        );

        return back()->with(
            'success',
            "Membership for {$user->email} has been suspended.",
        );
    }

    /**
     * Revoke a member's access in this tenant.
     */
    public function revoke(
        Request $request,
        Tenant $tenant,
        User $user,
        RevokeTenantMemberAction $action
    ): RedirectResponse {
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $action->execute(
            $membership,
            $request->user(),
        );

        return back()->with(
            'success',
            "Membership for {$user->email} has been revoked.",
        );
    }

    /**
     * Reactivate a suspended member's access in this tenant.
     */
    public function reactivate(
        Request $request,
        Tenant $tenant,
        User $user,
        ReactivateTenantMemberAction $action
    ): RedirectResponse {
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $action->execute(
            $membership,
            $request->user(),
        );

        return back()->with(
            'success',
            "Membership for {$user->email} has been reactivated.",
        );
    }
}
