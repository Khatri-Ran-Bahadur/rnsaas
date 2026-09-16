<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('country')->default('Global');
            $table->string('tax_registration_number')->nullable();
            $table->string('registered_business_name')->nullable();
            $table->string('tax_authority_name')->nullable();
            $table->string('tax_regime')->default('vat'); // vat, gst, sst, sales_tax, custom
            $table->string('reporting_frequency')->default('quarterly'); // monthly, bimonthly, quarterly, semi_annual, annual
            $table->string('accounting_method')->default('accrual'); // accrual, cash
            $table->foreignId('default_sales_tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('default_purchase_tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('output_tax_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('input_tax_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('withholding_tax_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('tax_settlement_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->string('default_pricing_mode')->default('exclusive'); // inclusive, exclusive
            $table->boolean('allow_cashier_tax_override')->default(false);
            $table->string('rounding_level')->default('line'); // line, tax_group, invoice_total
            $table->integer('rounding_precision')->default(2);
            $table->string('rounding_direction')->default('half_up'); // half_up, floor, ceil, round_to_5_cents
            $table->boolean('display_tax_summary_on_invoices')->default(true);
            $table->boolean('enable_einvoice_compliance')->default(true);
            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
