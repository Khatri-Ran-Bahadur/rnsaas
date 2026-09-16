<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Subscription\Models\Feature;
use Modules\Subscription\Models\Plan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('subscription_features') || ! Schema::hasTable('subscription_plans')) {
            return;
        }

        $feature = Feature::query()->firstOrCreate(
            ['slug' => 'ai'],
            [
                'public_id' => (string) Str::ulid(),
                'name' => 'AI Assistant & Copilot',
                'description' => 'Autonomous AI ERP Copilot, Natural Language Business Reporting, and Smart Assistant',
                'module' => 'ai',
                'is_active' => true,
                'sort_order' => 50,
            ]
        );

        // Attach to Enterprise / Top Tier plans
        $enterprisePlans = Plan::query()
            ->where(function ($q) {
                $q->where('slug', 'like', '%enterprise%')
                    ->orWhere('name', 'like', '%enterprise%')
                    ->orWhere('slug', 'like', '%pro%')
                    ->orWhere('slug', 'like', '%business%');
            })
            ->get();

        if ($enterprisePlans->isEmpty()) {
            // If specific named plans not found, attach to all existing plans
            $enterprisePlans = Plan::all();
        }

        foreach ($enterprisePlans as $plan) {
            $plan->features()->syncWithoutDetaching([$feature->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('subscription_features') || ! Schema::hasTable('subscription_plan_features')) {
            return;
        }

        $feature = Feature::query()->where('slug', 'ai')->first();
        if ($feature) {
            $feature->plans()->detach();
        }
    }
};
