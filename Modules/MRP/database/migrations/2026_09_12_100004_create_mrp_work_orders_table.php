<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_work_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('wo_number')->index();
            $table->foreignId('bom_id')->nullable()->constrained('mrp_boms')->nullOnDelete();
            $table->foreignId('work_center_id')->nullable()->constrained('mrp_work_centers')->nullOnDelete();
            $table->string('product_name');
            $table->string('product_sku')->index();
            $table->string('bom_version')->default('v1.0');
            $table->decimal('planned_qty', 10, 2)->default(1.00);
            $table->decimal('produced_qty', 10, 2)->default(0.00);
            $table->decimal('rejected_qty', 10, 2)->default(0.00);
            $table->string('unit')->default('Unit');
            $table->string('branch')->nullable();
            $table->string('priority')->default('Normal');
            $table->string('status')->default('Draft');
            $table->integer('progress_percent')->default(0);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_work_orders');
    }
};
