<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Subscription\Models\Feature;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('subscription_features')) {
            return;
        }

        Feature::query()->firstOrCreate(
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('subscription_features')) {
            return;
        }

        Feature::query()->where('slug', 'ai')->delete();
    }
};
