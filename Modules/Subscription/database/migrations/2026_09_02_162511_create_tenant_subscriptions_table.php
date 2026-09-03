<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->ulid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('plan_id')
                ->constrained('subscription_plans');

            $table->string('status', 30);

            $table->timestamp('starts_at');
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamp('current_period_starts_at');
            $table->timestamp('current_period_ends_at')->nullable();

            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('current_period_ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
