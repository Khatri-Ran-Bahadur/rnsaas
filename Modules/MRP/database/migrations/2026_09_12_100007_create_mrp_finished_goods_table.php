<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_finished_goods', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('mrp_work_orders')->nullOnDelete();
            $table->string('receipt_number')->index();
            $table->string('product_name');
            $table->string('product_sku')->index();
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('Unit');
            $table->string('lot_number')->nullable()->index();
            $table->string('warehouse_location')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'lot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_finished_goods');
    }
};
