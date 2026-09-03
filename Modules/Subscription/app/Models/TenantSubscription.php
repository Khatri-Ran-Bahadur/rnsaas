<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Tenancy\Models\Tenant;

/**
 * @property int $id
 * @property string $public_id
 * @property int $tenant_id
 * @property int $plan_id
 * @property SubscriptionStatus $status
 * @property \Carbon\CarbonInterface $starts_at
 * @property \Carbon\CarbonInterface|null $trial_ends_at
 * @property \Carbon\CarbonInterface $current_period_starts_at
 * @property \Carbon\CarbonInterface|null $current_period_ends_at
 * @property \Carbon\CarbonInterface|null $canceled_at
 * @property \Carbon\CarbonInterface|null $ends_at
 * @property array<string, mixed>|null $metadata
 */
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
    protected function activeOrTrialing(Builder $query): void
    {
        $query->whereIn('status', [
            SubscriptionStatus::Active->value,
            SubscriptionStatus::Trialing->value,
        ]);
    }

    #[Scope]
    protected function current(Builder $query): void
    {
        $query
            ->activeOrTrialing()
            ->where('starts_at', '<=', now())
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('current_period_ends_at')
                    ->orWhere('current_period_ends_at', '>', now());
            });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class,
            'tenant_id',
        );
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            Plan::class,
            'plan_id',
        );
    }
}