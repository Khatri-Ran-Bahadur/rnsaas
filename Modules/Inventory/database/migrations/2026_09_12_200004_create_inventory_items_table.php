<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();
            $table->string('name');
            $table->string('sku')->index();
            $table->string('barcode')->nullable()->index();
            $table->string('type')->default('stock'); // stock, service, combo, raw_material
            $table->decimal('selling_price', 12, 2)->default(0.00);
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->decimal('on_hand_stock', 12, 2)->default(0.00);
            $table->decimal('reserved_stock', 12, 2)->default(0.00);
            $table->decimal('reorder_point', 12, 2)->default(0.00);
            $table->string('status')->default('active');
            $table->boolean('is_pos_available')->default(true);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('track_stock')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->json('modifier_groups')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
