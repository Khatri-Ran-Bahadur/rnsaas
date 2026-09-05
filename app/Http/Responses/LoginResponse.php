<?php

namespace App\Http\Responses;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        // If user has an intended URL (e.g. they attempted to visit /admin/dashboard or /superadmin/dashboard)
        $intendedUrl = session()->pull('url.intended');

        if ($intendedUrl) {
            return redirect()->intended($intendedUrl);
        }

        // If SuperAdmin, route to platform control plane
        if ($user && $user->hasRole('SuperAdmin')) {
            return redirect()->route('superadmin.dashboard');
        }

        // If user has active organization memberships, route to tenant control plane
        if ($user) {
            $tenant = $user->tenants()
                ->wherePivot('status', TenantMembershipStatus::Active->value)
                ->orderBy('tenants.id')
                ->first();

            if ($tenant !== null) {
                $request->session()->put('current_tenant_id', $tenant->id);

                if (app()->bound(CurrentTenant::class)) {
                    app(CurrentTenant::class)->set($tenant);
                }

                return redirect()->route('admin.dashboard');
            }
        }

        // If user has no active organization membership, log out and inform them
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'You do not belong to an active organization.',
        ]);
    }
}
