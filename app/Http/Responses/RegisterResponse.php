<?php

namespace App\Http\Responses;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\SubscriptionBankTransfer;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
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
            return redirect()->route('superadmin.dashboard');
        }

        // If user has an active organization membership, route to subscription panel or admin
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

                $hasActiveSub = $tenant->subscriptions()
                    ->where('status', SubscriptionStatus::Active->value)
                    ->where(function ($query) {
                        $query->whereNull('current_period_ends_at')
                            ->orWhere('current_period_ends_at', '>=', now());
                    })
                    ->exists();

                if (! $hasActiveSub) {
                    $hasPendingTransfer = SubscriptionBankTransfer::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('status', 'pending')
                        ->exists();

                    $hasPendingSubscription = $tenant->subscriptions()
                        ->where('status', SubscriptionStatus::Pending->value)
                        ->exists();

                    if ($hasPendingTransfer || $hasPendingSubscription) {
                        return redirect()->route('admin.subscription.index')
                            ->with('info', 'Registration successful! Your subscription package has been submitted. Please complete your bank transfer payment and await SuperAdmin approval.');
                    }

                    return redirect()->route('admin.subscription.plans')
                        ->with('info', 'Registration successful! Please select a subscription package to activate organization access.');
                }

                return redirect()->route('admin.dashboard');
            }
        }

        return redirect()->route('admin.dashboard');
    }
}
