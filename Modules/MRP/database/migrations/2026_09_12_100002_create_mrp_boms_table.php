<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_boms', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('bom_number')->index();
            $table->string('product_name');
            $table->string('sku')->index();
            $table->string('bom_type')->default('Production BOM');
            $table->string('version')->default('v1.0');
            $table->decimal('output_qty', 10, 2)->default(1.00);
            $table->string('unit')->default('Unit');
            $table->decimal('estimated_cost', 12, 4)->default(0.0000);
            $table->decimal('unit_cost', 12, 4)->default(0.0000);
            $table->string('status')->default('Active');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(true);
            $table->string('routing_name')->nullable();
            $table->integer('levels_count')->default(1);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_boms');
    }
};
