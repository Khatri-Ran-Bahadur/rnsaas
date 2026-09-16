<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_bom_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bom_id')->constrained('mrp_boms')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->index();
            $table->decimal('quantity', 12, 4)->default(1.0000);
            $table->string('unit')->default('Unit');
            $table->decimal('unit_cost', 12, 4)->default(0.0000);
            $table->decimal('scrap_percentage', 5, 2)->default(0.00);
            $table->boolean('is_subassembly')->default(false);
            $table->unsignedBigInteger('subassembly_bom_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'bom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_bom_items');
    }
};
