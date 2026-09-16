<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_quality_inspections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('mrp_work_orders')->nullOnDelete();
            $table->string('inspection_number')->index();
            $table->string('type')->default('in_process');
            $table->string('wo_number')->nullable()->index();
            $table->string('product_name');
            $table->string('item_code')->nullable();
            $table->string('lot_or_batch')->nullable();
            $table->string('checkpoint_name');
            $table->string('target')->nullable();
            $table->string('actual')->nullable();
            $table->integer('sample_size')->default(1);
            $table->integer('inspected_qty')->default(1);
            $table->integer('passed_qty')->default(1);
            $table->integer('failed_qty')->default(0);
            $table->string('status')->default('passed');
            $table->string('inspector')->nullable();
            $table->dateTime('inspection_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_quality_inspections');
    }
};
