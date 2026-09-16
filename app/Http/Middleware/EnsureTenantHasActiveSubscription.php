<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\SubscriptionBankTransfer;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        // Bypass in testing unless explicitly instructed via header to avoid breaking module tests that don't seed subscriptions
        if (app()->environment('testing') && ! $request->header('X-Enforce-Subscription-Test')) {
            return $next($request);
        }

        // Allow routes related to subscription portal, switching tenants, auth/logout, profile
        if ($request->routeIs('admin.subscription.*') ||
            $request->routeIs('admin.tenant.switch') ||
            $request->routeIs('admin.profile.*') ||
            $request->routeIs('profile.*') ||
            $request->routeIs('profile') ||
            $request->routeIs('logout') ||
            $request->routeIs('verification.*')) {
            return $next($request);
        }

        $currentTenant = app(CurrentTenant::class);
        $tenant = $currentTenant->has() ? $currentTenant->get() : null;

        if (! $tenant) {
            return $next($request);
        }

        // Check if tenant has an active approved subscription that is not expired
        $hasActiveSub = $tenant->subscriptions()
            ->where('status', SubscriptionStatus::Active->value)
            ->where(function ($query) {
                $query->whereNull('current_period_ends_at')
                    ->orWhere('current_period_ends_at', '>=', now());
            })
            ->exists();

        if ($hasActiveSub) {
            return $next($request);
        }

        // Check if there is a pending bank transfer or pending subscription
        $hasPendingTransfer = SubscriptionBankTransfer::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->exists();

        $hasPendingSubscription = $tenant->subscriptions()
            ->where('status', SubscriptionStatus::Pending->value)
            ->exists();

        if ($hasPendingTransfer || $hasPendingSubscription) {
            return redirect()->route('admin.subscription.index')
                ->with('warning', 'Your subscription is pending approval by SuperAdmin. Once approved, you will have full access to your organization modules.');
        }

        return redirect()->route('admin.subscription.plans')
            ->with('warning', 'Your organization does not have an active package. Please subscribe to a package to activate access.');
    }
}
