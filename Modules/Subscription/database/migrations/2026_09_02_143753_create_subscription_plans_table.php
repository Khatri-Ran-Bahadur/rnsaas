<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();

            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2)->default(0);

            $table->string('currency', 3)->default('USD');

            $table->string('billing_cycle', 20);

            $table->unsignedSmallInteger('trial_days')->default(0);

            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
