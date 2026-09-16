<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->integer('priority')->default(10);
            $table->string('transaction_type')->default('sales'); // sales, purchases, both
            $table->string('sales_channel')->default('all');
            $table->string('customer_type')->default('b2c_individual');
            $table->string('item_type')->default('all');
            $table->foreignId('applied_tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->boolean('tax_inclusive_mode')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
