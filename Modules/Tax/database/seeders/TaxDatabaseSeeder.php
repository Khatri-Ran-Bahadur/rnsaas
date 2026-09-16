<?php

namespace Modules\Tax\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tax\Models\TaxCategory;
use Modules\Tax\Models\TaxExemption;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxRule;
use Modules\Tax\Models\TaxSetting;
use Modules\Tax\Models\TaxType;
use Modules\Tenancy\Models\Tenant;

class TaxDatabaseSeeder extends Seeder
{
    public function run(?int $targetTenantId = null): void
    {
        $tenants = $targetTenantId
            ? Tenant::where('id', $targetTenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $tenants = collect([(object) ['id' => $targetTenantId ?: 1]]);
        }

        foreach ($tenants as $tenant) {
            $tenantId = $tenant->id;

            // 1. Tax Types
            $vatGst = TaxType::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'VAT_GST'],
                [
                    'name' => 'Value Added Tax (VAT) / Goods & Services Tax (GST)',
                    'scope' => 'both',
                    'status' => 'active',
                    'description' => 'Multi-stage destination-based consumption tax collected on output and claimed on input.',
                ]
            );

            $sst = TaxType::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'SST'],
                [
                    'name' => 'Sales & Service Tax (SST)',
                    'scope' => 'sales',
                    'status' => 'active',
                    'description' => 'Single-stage tax levied on manufactured goods sales and taxable prescribed services.',
                ]
            );

            $wht = TaxType::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'WHT'],
                [
                    'name' => 'Withholding Tax (WHT)',
                    'scope' => 'purchases',
                    'status' => 'active',
                    'description' => 'Tax deducted at source from payments to non-residents, contractors, or specific service providers.',
                ]
            );

            $excise = TaxType::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'EXCISE'],
                [
                    'name' => 'Excise & Special Consumption Tax',
                    'scope' => 'sales',
                    'status' => 'active',
                    'description' => 'Duty levied on specific commodities such as alcohol, tobacco, sugary beverages, or motor vehicles.',
                ]
            );

            // 2. Tax Categories
            $standardCat = TaxCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'STANDARD'],
                [
                    'name' => 'Standard Rateable',
                    'status' => 'active',
                    'description' => 'Default taxable category for general goods, commodities, and commercial services.',
                ]
            );

            $reducedCat = TaxCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'REDUCED'],
                [
                    'name' => 'Reduced Concessionary',
                    'status' => 'active',
                    'description' => 'Prescribed concessions for essential food items, public utilities, and educational materials.',
                ]
            );

            $zeroCat = TaxCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'ZERO_RATED'],
                [
                    'name' => 'Zero-Rated Supplies',
                    'status' => 'active',
                    'description' => 'Taxable at 0%. Applicable to direct export of goods and international transport.',
                ]
            );

            $exemptCat = TaxCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'EXEMPT'],
                [
                    'name' => 'Statutory Exempt Supplies',
                    'status' => 'active',
                    'description' => 'Supplies specifically exempt from tax by law (e.g. healthcare, residential lease).',
                ]
            );

            // 3. Tax Rates
            $sr8 = TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'SR-8'],
                [
                    'tax_type_id' => $vatGst->id,
                    'name' => 'Standard Rate (8%)',
                    'tax_category' => 'standard',
                    'rate_type' => 'percentage',
                    'rate' => 8.0000,
                    'fixed_amount' => null,
                    'effective_from' => '2024-03-01',
                    'effective_until' => '2026-12-31',
                    'timeline_status' => 'active',
                    'country' => 'Global',
                    'region' => 'All Regions',
                    'is_recoverable' => true,
                    'is_compound' => false,
                    'description' => 'Standard prevailing tax rate for commercial goods and services.',
                ]
            );

            $sr10Fut = TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'SR-10-FUT'],
                [
                    'tax_type_id' => $vatGst->id,
                    'name' => 'Scheduled Future Rate (10%)',
                    'tax_category' => 'standard',
                    'rate_type' => 'percentage',
                    'rate' => 10.0000,
                    'fixed_amount' => null,
                    'effective_from' => '2027-01-01',
                    'effective_until' => null,
                    'timeline_status' => 'scheduled',
                    'country' => 'Global',
                    'region' => 'All Regions',
                    'is_recoverable' => true,
                    'is_compound' => false,
                    'description' => 'Statutory rate increase gazetted for implementation in 2027.',
                ]
            );

            $red5 = TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'RED-5'],
                [
                    'tax_type_id' => $vatGst->id,
                    'name' => 'Reduced Rate (5%)',
                    'tax_category' => 'reduced',
                    'rate_type' => 'percentage',
                    'rate' => 5.0000,
                    'fixed_amount' => null,
                    'effective_from' => '2024-01-01',
                    'effective_until' => null,
                    'timeline_status' => 'active',
                    'country' => 'Global',
                    'region' => 'All Regions',
                    'is_recoverable' => true,
                    'is_compound' => false,
                    'description' => 'Concessionary rate for fresh groceries, books, and essential items.',
                ]
            );

            $zero0 = TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'ZERO-0'],
                [
                    'tax_type_id' => $vatGst->id,
                    'name' => 'Zero-Rated Supply (0%)',
                    'tax_category' => 'zero_rated',
                    'rate_type' => 'percentage',
                    'rate' => 0.0000,
                    'fixed_amount' => null,
                    'effective_from' => '2024-01-01',
                    'effective_until' => null,
                    'timeline_status' => 'active',
                    'country' => 'Global',
                    'region' => 'International',
                    'is_recoverable' => true,
                    'is_compound' => false,
                    'description' => 'Zero-rate for direct export of goods and designated services.',
                ]
            );

            $sst6 = TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'SST-6'],
                [
                    'tax_type_id' => $sst->id,
                    'name' => 'Service Tax (6%)',
                    'tax_category' => 'standard',
                    'rate_type' => 'percentage',
                    'rate' => 6.0000,
                    'fixed_amount' => null,
                    'effective_from' => '2024-01-01',
                    'effective_until' => null,
                    'timeline_status' => 'active',
                    'country' => 'Global',
                    'region' => 'All Regions',
                    'is_recoverable' => false,
                    'is_compound' => false,
                    'description' => 'Service tax on F&B, telecommunication, and IT services.',
                ]
            );

            // 4. Tax Rules
            TaxRule::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => 'Domestic B2C Retail & POS Rule'],
                [
                    'priority' => 10,
                    'transaction_type' => 'sales',
                    'sales_channel' => 'pos,retail,ecommerce',
                    'customer_type' => 'b2c_individual',
                    'item_type' => 'all',
                    'applied_tax_rate_id' => $sr8->id,
                    'tax_inclusive_mode' => true,
                    'is_active' => true,
                    'description' => 'Applies 8% tax-inclusive pricing for all walk-in retail, POS touchscreen, and domestic consumer orders.',
                ]
            );

            TaxRule::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => 'Direct Export to International Customer (Zero-Rated)'],
                [
                    'priority' => 20,
                    'transaction_type' => 'sales',
                    'sales_channel' => 'all',
                    'customer_type' => 'international_export',
                    'item_type' => 'goods',
                    'applied_tax_rate_id' => $zero0->id,
                    'tax_inclusive_mode' => false,
                    'is_active' => true,
                    'description' => 'Automatically overrides sales tax to 0% Zero-Rated when customer billing/shipping destination is outside the domestic jurisdiction.',
                ]
            );

            TaxRule::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => 'Commercial B2B Invoice Dispatch'],
                [
                    'priority' => 15,
                    'transaction_type' => 'sales',
                    'sales_channel' => 'accounting_invoice,wholesale',
                    'customer_type' => 'b2b_registered',
                    'item_type' => 'all',
                    'applied_tax_rate_id' => $sr8->id,
                    'tax_inclusive_mode' => false,
                    'is_active' => true,
                    'description' => 'Generates standard tax-exclusive B2B commercial tax invoices with itemized tax columns.',
                ]
            );

            // 5. Tax Exemptions
            TaxExemption::updateOrCreate(
                ['tenant_id' => $tenantId, 'certificate_number' => 'EXP-2026-981'],
                [
                    'entity_name' => 'Apex Global Logistics Pte Ltd',
                    'entity_type' => 'customer',
                    'exemption_type' => 'export_consignment',
                    'valid_from' => '2026-01-01',
                    'valid_until' => '2026-12-31',
                    'status' => 'active',
                    'issuing_authority' => 'National Customs Authority',
                ]
            );

            TaxExemption::updateOrCreate(
                ['tenant_id' => $tenantId, 'certificate_number' => 'GOV-MED-442'],
                [
                    'entity_name' => 'National University Medical Research',
                    'entity_type' => 'customer',
                    'exemption_type' => 'statutory_education',
                    'valid_from' => '2025-06-01',
                    'valid_until' => '2027-05-31',
                    'status' => 'active',
                    'issuing_authority' => 'Ministry of Finance',
                ]
            );

            // 6. Tax Settings Profile
            TaxSetting::updateOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'country' => $tenant->country_code ?? 'Global',
                    'tax_registration_number' => null,
                    'registered_business_name' => $tenant->name ?? 'Organization',
                    'tax_authority_name' => null,
                    'tax_regime' => 'vat',
                    'reporting_frequency' => 'monthly',
                    'accounting_method' => 'accrual',
                    'default_sales_tax_rate_id' => $sr8->id,
                    'default_purchase_tax_rate_id' => $sr8->id,
                    'default_pricing_mode' => 'exclusive',
                    'allow_cashier_tax_override' => false,
                    'rounding_level' => 'line',
                    'rounding_precision' => 2,
                    'rounding_direction' => 'half_up',
                    'display_tax_summary_on_invoices' => true,
                    'enable_einvoice_compliance' => false,
                ]
            );
        }
    }
}
