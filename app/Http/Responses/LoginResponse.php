<?php

namespace App\Http\Responses;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\SubscriptionBankTransfer;
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

        // If SuperAdmin, route to platform control plane
        if ($user && $user->hasRole('SuperAdmin')) {
            $intendedUrl = session()->pull('url.intended');
            if ($intendedUrl && ! str_contains($intendedUrl, '/admin')) {
                return redirect()->intended($intendedUrl);
            }

            return redirect()->route('superadmin.dashboard');
        }

        // If user has active organization memberships, route to tenant control plane or subscription panel
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

                // Verify active approved subscription
                $hasActiveSub = $tenant->subscriptions()
                    ->where('status', SubscriptionStatus::Active->value)
                    ->where(function ($query) {
                        $query->whereNull('current_period_ends_at')
                            ->orWhere('current_period_ends_at', '>=', now());
                    })
                    ->exists();

                if (! $hasActiveSub) {
                    session()->forget('url.intended');

                    $hasPendingTransfer = SubscriptionBankTransfer::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('status', 'pending')
                        ->exists();

                    $hasPendingSubscription = $tenant->subscriptions()
                        ->where('status', SubscriptionStatus::Pending->value)
                        ->exists();

                    if ($hasPendingTransfer || $hasPendingSubscription) {
                        return redirect()->route('admin.subscription.index');
                    }

                    return redirect()->route('admin.subscription.plans');
                }

                $intendedUrl = session()->pull('url.intended');
                if ($intendedUrl) {
                    return redirect()->intended($intendedUrl);
                }

                return redirect()->route('admin.dashboard');
            }
        }

        $intendedUrl = session()->pull('url.intended');
        if ($intendedUrl) {
            return redirect()->intended($intendedUrl);
        }

        return redirect()->intended(config('fortify.home', '/'));
    }
}
