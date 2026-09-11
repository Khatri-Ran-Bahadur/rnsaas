<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_vendor_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('vendor_payment_id');
            $table->foreign('vendor_payment_id', 'avpa_payment_fk')
                ->references('id')
                ->on('accounting_vendor_payments')
                ->cascadeOnDelete();

            $table->foreignId('purchase_bill_id');
            $table->foreign('purchase_bill_id', 'avpa_bill_fk')
                ->references('id')
                ->on('accounting_purchase_bills')
                ->restrictOnDelete();

            $table->decimal('allocated_amount', 20, 6);

            $table->timestamps();

            $table->unique(['vendor_payment_id', 'purchase_bill_id'], 'avpa_payment_bill_unique');

            $table->index(['tenant_id', 'vendor_payment_id'], 'avpa_tenant_payment_idx');
            $table->index(['tenant_id', 'purchase_bill_id'], 'avpa_tenant_bill_idx');
            $table->index('purchase_bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_vendor_payment_allocations');
    }
};
