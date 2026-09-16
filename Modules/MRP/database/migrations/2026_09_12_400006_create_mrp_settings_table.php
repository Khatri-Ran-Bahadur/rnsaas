<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->boolean('auto_reserve_materials')->default(true);
            $table->boolean('allow_negative_raw_materials')->default(false);
            $table->boolean('require_qa_approval_before_fg')->default(true);
            $table->decimal('default_scrap_percentage', 5, 2)->default(2.00);
            $table->integer('mrp_planning_horizon_days')->default(30);
            $table->decimal('default_overhead_allocation_rate', 5, 2)->default(15.00);
            $table->boolean('track_lot_genealogy')->default(true);
            $table->boolean('require_manager_override_for_scrap')->default(true);

            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_settings');
    }
};
