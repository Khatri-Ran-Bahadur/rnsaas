<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

/**
 * @property int $id
 * @property string $public_id
 * @property int $tenant_id
 * @property int|null $subscription_id
 * @property string $provider
 * @property string|null $provider_transaction_id
 * @property string $idempotency_key
 * @property string $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property PaymentType $type
 * @property \Carbon\CarbonInterface|null $paid_at
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'public_id',
    'tenant_id',
    'subscription_id',
    'provider',
    'provider_transaction_id',
    'idempotency_key',
    'amount',
    'currency',
    'status',
    'type',
    'paid_at',
    'metadata',
])]
class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'subscription_id',
        'provider',
        'provider_transaction_id',
        'idempotency_key',
        'amount',
        'currency',
        'status',
        'type',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'type' => PaymentType::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class,
            'tenant_id',
        );
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            TenantSubscription::class,
            'subscription_id',
        );
    }
}