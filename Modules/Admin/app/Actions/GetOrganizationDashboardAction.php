<?php

namespace Modules\Admin\Actions;

use App\Support\Tenancy\CurrentTenant;
use Modules\Admin\DTOs\OrganizationDashboardData;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;

final class GetOrganizationDashboardAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(): OrganizationDashboardData
    {
        $tenant = $this->currentTenant->get();

        $membershipCounts = $tenant->users()
            ->select('tenant_user.status')
            ->get()
            ->groupBy(fn ($user) => $user->pivot->status->value ?? $user->pivot->status)
            ->map(fn ($users) => $users->count());

        $subscription = TenantSubscription::query()
            ->with('plan')
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();

        return new OrganizationDashboardData(
            tenant: [
                'public_id' => $tenant->public_id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'industry' => $tenant->industry,
                'status' => $tenant->status->value,
                'country_code' => $tenant->country_code,
                'timezone' => $tenant->timezone,
                'locale' => $tenant->locale,
                'currency' => $tenant->currency,
            ],
            members: [
                'total' => $membershipCounts->sum(),
                'active' => $membershipCounts->get(
                    TenantMembershipStatus::Active->value,
                    0,
                ),
                'invited' => $membershipCounts->get(
                    TenantMembershipStatus::Invited->value,
                    0,
                ),
                'suspended' => $membershipCounts->get(
                    TenantMembershipStatus::Suspended->value,
                    0,
                ),
                'revoked' => $membershipCounts->get(
                    TenantMembershipStatus::Revoked->value,
                    0,
                ),
            ],
            subscription: $subscription === null
                ? [
                    'exists' => false,
                    'status' => null,
                    'plan' => null,
                    'current_period_ends_at' => null,
                    'trial_ends_at' => null,
                ]
                : [
                    'exists' => true,
                    'status' => $subscription->status->value,
                    'plan' => $subscription->plan?->name,
                    'current_period_ends_at' => $subscription
                        ->current_period_ends_at
                        ?->toIso8601String(),
                    'trial_ends_at' => $subscription->trial_ends_at
                        ?->toIso8601String(),
                ],
        );
    }
}
