<?php

namespace Modules\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;

class CreateManualPaymentAction
{
    public function handle(
        Tenant $tenant,
        string $amount,
        string $currency,
        PaymentType $type,
        string $idempotencyKey,
        ?TenantSubscription $subscription = null,
        array $metadata = [],
    ): PaymentTransaction {
        return DB::transaction(function () use (
            $tenant,
            $amount,
            $currency,
            $type,
            $idempotencyKey,
            $subscription,
            $metadata,
        ): PaymentTransaction {
            return PaymentTransaction::create([
                'public_id' => (string) Str::ulid(),
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription?->id,
                'provider' => 'bank_transfer',
                'provider_transaction_id' => null,
                'idempotency_key' => $idempotencyKey,
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'status' => PaymentStatus::Pending,
                'type' => $type,
                'paid_at' => null,
                'metadata' => $metadata,
            ]);
        });
    }
}
