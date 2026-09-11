<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_vendors', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('vendor_code', 50);
            $table->string('name', 255);

            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('tax_number', 100)->nullable();

            $table->string('billing_address_line_1', 255)->nullable();
            $table->string('billing_address_line_2', 255)->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_state', 100)->nullable();
            $table->string('billing_postcode', 30)->nullable();
            $table->string('billing_country', 2)->nullable();

            $table->foreignId('payable_account_id')
                ->nullable()
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->decimal('credit_limit', 20, 6)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(0);

            $table->string('status', 30)->default('active');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'vendor_code']);

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'payable_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_vendors');
    }
};
