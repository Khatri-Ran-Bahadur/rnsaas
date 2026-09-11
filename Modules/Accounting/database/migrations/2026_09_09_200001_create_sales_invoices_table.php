<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('accounting_customers')->restrictOnDelete();

            $table->string('invoice_number', 50);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('currency', 3)->default('MYR');

            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('discount_total', 20, 6)->default(0);
            $table->decimal('tax_total', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);

            $table->string('status', 20)->default('draft');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('voided_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'invoice_date']);
            $table->index(['tenant_id', 'due_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id', 'status']);
            $table->index(['tenant_id', 'journal_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_sales_invoices');
    }
};
