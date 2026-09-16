<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxSetting;
use Modules\Tax\Models\TaxType;
use Modules\Tenancy\Models\Tenant;

class TaxSettingController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $tenant = Tenant::find($tenantId);

        $taxSetting = TaxSetting::where('tenant_id', $tenantId)->first();

        // If tenant has configured country in Company Profile, synchronize with TaxSetting
        $tenantCountryCode = strtoupper((string) ($tenant?->country_code ?: ''));
        $tenantPAN = $tenant?->settings['tax_id'] ?? null;

        if (! $taxSetting) {
            $taxSetting = TaxSetting::create([
                'tenant_id' => $tenantId,
                'country' => $tenantCountryCode ?: 'NP',
                'tax_registration_number' => $tenantPAN,
                'registered_business_name' => $tenant?->name ?: 'My Business',
                'tax_authority_name' => $tenantCountryCode === 'NP' ? 'Inland Revenue Department (IRD / आन्तरिक राजस्व विभाग)' : null,
                'tax_regime' => 'vat',
                'reporting_frequency' => 'monthly',
                'accounting_method' => 'accrual',
                'default_pricing_mode' => 'exclusive',
                'allow_cashier_tax_override' => false,
                'rounding_level' => 'line',
                'rounding_precision' => 2,
                'rounding_direction' => 'half_up',
                'display_tax_summary_on_invoices' => true,
                'enable_einvoice_compliance' => false,
            ]);
        } else {
            // Auto-clean old default 'MY' if tenant is Nepal or set differently in company profile
            $needsUpdate = false;
            $updates = [];

            if ($tenantCountryCode && in_array($taxSetting->country, ['MY', 'Global', null, ''], true) && $tenantCountryCode !== 'MY') {
                $updates['country'] = $tenantCountryCode;
                if ($tenantCountryCode === 'NP') {
                    $updates['tax_regime'] = 'vat';
                    $updates['tax_authority_name'] = 'Inland Revenue Department (IRD / आन्तरिक राजस्व विभाग)';
                }
                $needsUpdate = true;
            }

            if ($tenantPAN && empty($taxSetting->tax_registration_number)) {
                $updates['tax_registration_number'] = $tenantPAN;
                $needsUpdate = true;
            }

            if ($tenant?->name && ($taxSetting->registered_business_name === 'SathiSaaS Technologies Sdn. Bhd.' || $taxSetting->registered_business_name === 'My Business')) {
                $updates['registered_business_name'] = $tenant->name;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $taxSetting->update($updates);
            }
        }

        $defaultRate = TaxRate::where('tenant_id', $tenantId)
            ->where('timeline_status', 'active')
            ->where('tax_category', 'standard')
            ->first()
            ?? TaxRate::where('tenant_id', $tenantId)->first();

        $settings = [
            'country' => $taxSetting->country,
            'tax_registration_number' => $taxSetting->tax_registration_number,
            'registered_business_name' => $taxSetting->registered_business_name,
            'tax_authority_name' => $taxSetting->tax_authority_name ?? '',
            'tax_regime' => $taxSetting->tax_regime,
            'reporting_frequency' => $taxSetting->reporting_frequency,
            'accounting_method' => $taxSetting->accounting_method,
            'default_sales_tax_rate_id' => $taxSetting->default_sales_tax_rate_id ?? $defaultRate?->id ?? '',
            'default_purchase_tax_rate_id' => $taxSetting->default_purchase_tax_rate_id ?? $defaultRate?->id ?? '',
            'default_pricing_mode' => $taxSetting->default_pricing_mode,
            'allow_cashier_tax_override' => (bool) $taxSetting->allow_cashier_tax_override,
            'rounding_level' => $taxSetting->rounding_level,
            'rounding_precision' => (int) $taxSetting->rounding_precision,
            'rounding_direction' => $taxSetting->rounding_direction,
            'display_tax_summary_on_invoices' => (bool) $taxSetting->display_tax_summary_on_invoices,
            'enable_einvoice_compliance' => (bool) $taxSetting->enable_einvoice_compliance,
        ];

        $availableRates = TaxRate::where('tenant_id', $tenantId)
            ->where('timeline_status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'rate', 'tax_category'])
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'name' => "{$r->name} (".number_format((float) $r->rate, 2).'%)',
                    'code' => $r->code,
                    'rate' => (float) $r->rate,
                    'category' => $r->tax_category,
                ];
            })->toArray();

        $countries = [
            ['code' => 'NP', 'name' => 'Nepal 🇳🇵 (PAN / VAT 13%)', 'regime' => 'vat'],
            ['code' => 'IN', 'name' => 'India 🇮🇳 (GST 18% / 12% / 5%)', 'regime' => 'gst'],
            ['code' => 'MY', 'name' => 'Malaysia 🇲🇾 (SST 8% / 6%)', 'regime' => 'sst'],
            ['code' => 'GB', 'name' => 'United Kingdom 🇬🇧 (VAT 20%)', 'regime' => 'vat'],
            ['code' => 'US', 'name' => 'United States 🇺🇸 (State Sales Tax)', 'regime' => 'sales_tax'],
            ['code' => 'SG', 'name' => 'Singapore 🇸🇬 (GST 9%)', 'regime' => 'gst'],
            ['code' => 'AU', 'name' => 'Australia 🇦🇺 (GST 10%)', 'regime' => 'gst'],
            ['code' => 'CA', 'name' => 'Canada 🇨🇦 (GST / HST / PST)', 'regime' => 'gst'],
            ['code' => 'AE', 'name' => 'United Arab Emirates 🇦🇪 (VAT 5%)', 'regime' => 'vat'],
            ['code' => 'SA', 'name' => 'Saudi Arabia 🇸🇦 (VAT 15%)', 'regime' => 'vat'],
            ['code' => 'GL', 'name' => 'Other / Global Universal 🌐', 'regime' => 'custom'],
        ];

        return Inertia::render('Tax/Settings/Index', [
            'settings' => $settings,
            'availableRates' => $availableRates,
            'countries' => $countries,
            'tenantCountryCode' => $tenantCountryCode,
        ]);
    }

    /**
     * One-click standard statutory tax preset generator for country.
     */
    public function applyPreset(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $countryCode = strtoupper((string) $request->input('country_code', 'NP'));

        $taxTypeVat = TaxType::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Value Added Tax'],
            ['code' => 'VAT', 'is_active' => true, 'description' => 'Consumption tax on value added']
        );

        $taxTypeWithholding = TaxType::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Withholding Tax / TDS'],
            ['code' => 'TDS', 'is_active' => true, 'description' => 'Tax deducted at source on payments']
        );

        $defaultSalesRateId = null;

        if ($countryCode === 'NP') {
            // Nepal Standard IRD Tax Suite
            $standardVat = TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'VAT-13'],
                [
                    'tax_type_id' => $taxTypeVat->id,
                    'name' => 'Standard Rate (13%)',
                    'rate' => 13.00,
                    'tax_category' => 'standard',
                    'rate_type' => 'percentage',
                    'effective_from' => '2024-01-01',
                    'timeline_status' => 'active',
                    'is_recoverable' => true,
                    'country' => 'NP',
                    'description' => 'Standard 13% Value Added Tax on taxable goods and services under IRD',
                ]
            );
            $defaultSalesRateId = $standardVat->id;

            TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'VAT-EXEMPT'],
                [
                    'tax_type_id' => $taxTypeVat->id,
                    'name' => 'Exempt / Non-Taxable (0%)',
                    'rate' => 0.00,
                    'tax_category' => 'exempt',
                    'rate_type' => 'percentage',
                    'effective_from' => '2024-01-01',
                    'timeline_status' => 'active',
                    'is_recoverable' => false,
                    'country' => 'NP',
                    'description' => 'Non-taxable goods and services under Schedule 1 of VAT Act 2052 (आधारभूत खाद्यान्न, औषधि, पुस्तक, कृषि उत्पादन)',
                ]
            );

            TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'VAT-ZERO'],
                [
                    'tax_type_id' => $taxTypeVat->id,
                    'name' => 'Zero-Rated Export (0%)',
                    'rate' => 0.00,
                    'tax_category' => 'zero_rated',
                    'rate_type' => 'percentage',
                    'effective_from' => '2024-01-01',
                    'timeline_status' => 'active',
                    'is_recoverable' => true,
                    'country' => 'NP',
                    'description' => 'Export of goods and services outside Nepal (निकासी तथा निर्यात)',
                ]
            );

            TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'TDS-SRV-10'],
                [
                    'tax_type_id' => $taxTypeWithholding->id,
                    'name' => 'TDS Service Fees (10%)',
                    'rate' => 10.00,
                    'tax_category' => 'withholding',
                    'rate_type' => 'percentage',
                    'effective_from' => '2024-01-01',
                    'timeline_status' => 'active',
                    'country' => 'NP',
                    'description' => 'Tax deducted at source for professional/technical service contracts',
                ]
            );

            TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'TDS-GDS-1.5'],
                [
                    'tax_type_id' => $taxTypeWithholding->id,
                    'name' => 'TDS Goods Contract (1.5%)',
                    'rate' => 1.50,
                    'tax_category' => 'withholding',
                    'rate_type' => 'percentage',
                    'effective_from' => '2024-01-01',
                    'timeline_status' => 'active',
                    'country' => 'NP',
                    'description' => 'TDS on supply contracts exceeding threshold',
                ]
            );

            TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'TDS-RENT-10'],
                [
                    'tax_type_id' => $taxTypeWithholding->id,
                    'name' => 'TDS House Rent (10%)',
                    'rate' => 10.00,
                    'tax_category' => 'withholding',
                    'rate_type' => 'percentage',
                    'effective_from' => '2024-01-01',
                    'timeline_status' => 'active',
                    'country' => 'NP',
                    'description' => 'Local authority / IRD house rent TDS',
                ]
            );

            TaxSetting::updateOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'country' => 'NP',
                    'tax_regime' => 'vat',
                    'tax_authority_name' => 'Inland Revenue Department (IRD / आन्तरिक राजस्व विभाग)',
                    'default_sales_tax_rate_id' => $defaultSalesRateId,
                    'default_purchase_tax_rate_id' => $defaultSalesRateId,
                    'default_pricing_mode' => 'exclusive',
                ]
            );
        } elseif ($countryCode === 'IN') {
            $taxTypeGst = TaxType::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => 'Goods and Services Tax'],
                ['code' => 'GST', 'is_active' => true, 'description' => 'India GST']
            );

            $gst18 = TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GST-18'],
                [
                    'tax_type_id' => $taxTypeGst->id,
                    'name' => 'Standard GST (18%)',
                    'rate' => 18.00,
                    'tax_category' => 'standard',
                    'timeline_status' => 'active',
                    'is_recoverable' => true,
                    'country' => 'IN',
                ]
            );
            $defaultSalesRateId = $gst18->id;

            TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GST-12'],
                ['tax_type_id' => $taxTypeGst->id, 'name' => 'GST (12%)', 'rate' => 12.00, 'tax_category' => 'standard', 'timeline_status' => 'active', 'country' => 'IN']
            );
            TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GST-5'],
                ['tax_type_id' => $taxTypeGst->id, 'name' => 'Reduced GST (5%)', 'rate' => 5.00, 'tax_category' => 'reduced', 'timeline_status' => 'active', 'country' => 'IN']
            );
            TaxRate::updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GST-0'],
                ['tax_type_id' => $taxTypeGst->id, 'name' => 'Exempt GST (0%)', 'rate' => 0.00, 'tax_category' => 'exempt', 'timeline_status' => 'active', 'country' => 'IN']
            );

            TaxSetting::updateOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'country' => 'IN',
                    'tax_regime' => 'gst',
                    'tax_authority_name' => 'Goods and Services Tax Network (GSTN) / CBIC',
                    'default_sales_tax_rate_id' => $defaultSalesRateId,
                    'default_purchase_tax_rate_id' => $defaultSalesRateId,
                ]
            );
        }

        return redirect()->back()->with('success', "Standard statutory tax preset for {$countryCode} loaded successfully.");
    }

    public function update(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'country' => 'required|string|max:255',
            'tax_registration_number' => 'nullable|string|max:100',
            'registered_business_name' => 'required|string|max:255',
            'tax_authority_name' => 'nullable|string|max:255',
            'tax_regime' => 'required|string',
            'reporting_frequency' => 'required|in:monthly,bimonthly,quarterly,semi_annual,annual',
            'accounting_method' => 'required|in:accrual,cash',
            'default_sales_tax_rate_id' => 'nullable',
            'default_purchase_tax_rate_id' => 'nullable',
            'default_pricing_mode' => 'required|in:inclusive,exclusive',
            'allow_cashier_tax_override' => 'boolean',
            'rounding_level' => 'required|in:line,tax_group,invoice_total',
            'rounding_precision' => 'required|integer|in:2,3,4',
            'rounding_direction' => 'required|in:half_up,floor,ceil,round_to_5_cents',
            'display_tax_summary_on_invoices' => 'boolean',
            'enable_einvoice_compliance' => 'boolean',
        ]);

        if (empty($validated['default_sales_tax_rate_id'])) {
            $validated['default_sales_tax_rate_id'] = null;
        }

        if (empty($validated['default_purchase_tax_rate_id'])) {
            $validated['default_purchase_tax_rate_id'] = null;
        }

        TaxSetting::updateOrCreate(
            ['tenant_id' => $tenantId],
            $validated
        );

        return redirect()->back()->with('success', 'Tax system configuration saved successfully.');
    }
}
