<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_vendor_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('vendor_id')
                ->constrained('accounting_vendors')
                ->restrictOnDelete();

            $table->string('payment_number', 50);
            $table->date('payment_date');

            $table->decimal('amount', 20, 6)->default(0);
            $table->char('currency', 3)->default('MYR');

            $table->string('payment_method', 50)->default('bank_transfer');

            $table->foreignId('bank_account_id')
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');

            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'payment_number']);

            $table->index(['tenant_id', 'vendor_id']);
            $table->index(['tenant_id', 'payment_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'vendor_id', 'status']);
            $table->index(['tenant_id', 'journal_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_vendor_payments');
    }
};
