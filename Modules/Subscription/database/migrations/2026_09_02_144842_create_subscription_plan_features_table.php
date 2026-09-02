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
        Schema::create('subscription_plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')
                ->constrained('subscription_plans')
                ->cascadeOnDelete();

            $table->foreignId('feature_id')
                ->constrained('subscription_features')
                ->cascadeOnDelete();

            $table->boolean('enabled')->default(true);

            $table->json('limits')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->unique(['plan_id', 'feature_id']);

            $table->index(['feature_id', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_features');
    }
};
