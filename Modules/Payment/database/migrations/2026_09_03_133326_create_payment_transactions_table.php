<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('tenant_subscriptions')
                ->nullOnDelete();

            $table->string('provider', 50);

            $table->string('provider_transaction_id', 150)
                ->nullable();

            $table->string('idempotency_key', 150)
                ->unique();

            $table->decimal('amount', 12, 2);

            $table->string('currency', 3);

            $table->string('status', 30);

            $table->string('type', 30);

            $table->timestamp('paid_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'status'],
            );

            $table->index(
                ['subscription_id', 'status'],
            );

            $table->index(
                ['provider', 'provider_transaction_id'],
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};