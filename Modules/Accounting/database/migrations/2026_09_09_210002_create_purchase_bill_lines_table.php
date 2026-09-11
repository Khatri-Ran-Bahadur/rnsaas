<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_purchase_bill_lines', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('purchase_bill_id')
                ->constrained('accounting_purchase_bills')
                ->cascadeOnDelete();

            $table->unsignedInteger('line_number');
            $table->string('description', 500);

            $table->decimal('quantity', 20, 6)->default(1);
            $table->decimal('unit_price', 20, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('tax_rate', 10, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('total', 20, 6)->default(0);

            $table->foreignId('debit_account_id')
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->foreignId('tax_account_id')
                ->nullable()
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(['purchase_bill_id', 'line_number'], 'apbl_bill_line_unique');

            $table->index('purchase_bill_id');
            $table->index('debit_account_id');
            $table->index('tax_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_purchase_bill_lines');
    }
};
