<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_sales_invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('accounting_sales_invoices')->cascadeOnDelete();
            $table->foreignId('revenue_account_id')->constrained('accounting_accounts')->restrictOnDelete();

            $table->unsignedInteger('line_number');
            $table->string('description', 500);
            $table->decimal('quantity', 20, 6)->default(1);
            $table->decimal('unit_price', 20, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('tax_rate', 10, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('total', 20, 6)->default(0);

            $table->timestamps();

            $table->unique(['sales_invoice_id', 'line_number'], 'asil_invoice_line_unique');
            $table->index('sales_invoice_id');
            $table->index('revenue_account_id');
            $table->index(['tenant_id', 'sales_invoice_id'], 'asil_tenant_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_sales_invoice_lines');
    }
};
