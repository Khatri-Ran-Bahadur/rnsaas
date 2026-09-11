<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_customer_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_payment_id');
            $table->foreign('customer_payment_id', 'acpa_payment_fk')
                ->references('id')
                ->on('accounting_customer_payments')
                ->cascadeOnDelete();

            $table->foreignId('sales_invoice_id');
            $table->foreign('sales_invoice_id', 'acpa_invoice_fk')
                ->references('id')
                ->on('accounting_sales_invoices')
                ->restrictOnDelete();

            $table->decimal('allocated_amount', 20, 6);

            $table->timestamps();

            $table->unique(['customer_payment_id', 'sales_invoice_id'], 'acpa_payment_invoice_unique');
            $table->index('sales_invoice_id');
            $table->index(['tenant_id', 'sales_invoice_id'], 'acpa_tenant_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_customer_payment_allocations');
    }
};
