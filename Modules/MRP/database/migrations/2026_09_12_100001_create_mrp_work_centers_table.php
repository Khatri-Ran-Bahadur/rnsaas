<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_work_centers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code')->index();
            $table->string('name');
            $table->string('branch')->nullable();
            $table->string('type')->default('machine');
            $table->decimal('capacity_hours_per_day', 8, 2)->default(16.00);
            $table->decimal('hourly_rate', 10, 2)->default(45.00);
            $table->decimal('hourly_cost', 10, 2)->default(45.00);
            $table->integer('efficiency_percentage')->default(90);
            $table->integer('oee_percentage')->default(85);
            $table->integer('utilization_percent')->default(80);
            $table->string('status')->default('operational');
            $table->integer('machines_count')->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_work_centers');
    }
};
