<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'plan_id',
    'status',
    'starts_at',
    'trial_ends_at',
    'current_period_starts_at',
    'current_period_ends_at',
    'canceled_at',
    'ends_at',
    'metadata',
])]
class TenantSubscription extends Model
{
    use HasFactory;

    protected $table = 'tenant_subscriptions';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'plan_id',
        'status',
        'starts_at',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'canceled_at',
        'ends_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    #[Scope]
    protected function search(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $query->whereHas(
            'tenant',
            fn (Builder $tenantQuery) => $tenantQuery
                ->where('name', 'like', "%{$search}%"),
        );
    }

    #[Scope]
    protected function withStatus(
        Builder $query,
        ?string $status,
    ): void {
        if ($status === null || $status === '') {
            return;
        }

        $query->where('status', $status);
    }

    #[Scope]
    protected function forPlan(
        Builder $query,
        ?int $planId,
    ): void {
        if ($planId === null) {
            return;
        }

        $query->where('plan_id', $planId);
    }

    #[Scope]
    protected function withBillingCycle(
        Builder $query,
        ?string $billingCycle,
    ): void {
        if ($billingCycle === null || $billingCycle === '') {
            return;
        }

        $query->whereHas(
            'plan',
            fn (Builder $planQuery) => $planQuery
                ->where('billing_cycle', $billingCycle),
        );
    }

    #[Scope]
    protected function activeOrTrialing(Builder $query): void
    {
        $query->whereIn('status', [
            SubscriptionStatus::Active->value,
            SubscriptionStatus::Trialing->value,
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            PaymentTransaction::class,
            'subscription_id',
        );
    }
}
