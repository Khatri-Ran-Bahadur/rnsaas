<?php

namespace Modules\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Tenancy\Models\Tenant;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'tenant_id' => Tenant::factory(),
            'subscription_id' => null,
            'provider' => 'bank_transfer',
            'provider_transaction_id' => null,
            'idempotency_key' => (string) Str::ulid(),
            'amount' => '49.00',
            'currency' => 'USD',
            'status' => PaymentStatus::Pending,
            'type' => PaymentType::Subscription,
            'paid_at' => null,
            'metadata' => null,
        ];
    }
}
