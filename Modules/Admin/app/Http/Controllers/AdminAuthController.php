<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;

final class AdminAuthController
{
    /**
     * Display the organization admin login entry point.
     */
    public function showLogin(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            if ($user->hasRole('SuperAdmin')) {
                return redirect()->route('superadmin.dashboard');
            }

            $hasActiveTenant = $user->tenants()
                ->wherePivot('status', TenantMembershipStatus::Active->value)
                ->exists();

            if ($hasActiveTenant) {
                return redirect()->route('admin.dashboard');
            }

            abort(403, 'You do not belong to an active organization.');
        }

        // Set intended URL so successful Fortify authentication redirects to organization dashboard
        $request->session()->put('url.intended', route('admin.dashboard'));

        return Inertia::render('Admin/Auth/Login', [
            'status' => session('status'),
        ]);
    }
}
