<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Domain\Enums\AccountClassification;
use Modules\Accounting\Domain\Enums\AccountNormalBalance;
use Modules\Accounting\Domain\Enums\FinancialStatement;
use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AccountGroup;
use Modules\Accounting\Models\AccountType;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxSetting;
use Modules\Tax\Models\TaxType;
use Modules\Tenancy\Models\Tenant;

class TaxAccountMappingController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $tenant = Tenant::find($tenantId);

        $countryCode = strtoupper((string) ($tenant?->country_code ?: $tenant?->settings['country_code'] ?? 'NP'));
        $countryName = match ($countryCode) {
            'NP' => 'Nepal',
            'IN' => 'India',
            'MY' => 'Malaysia',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'AU' => 'Australia',
            'CA' => 'Canada',
            default => $tenant?->settings['country'] ?? $countryCode,
        };

        $taxRegime = match ($countryCode) {
            'IN' => 'gst',
            'US' => 'sales_tax',
            'MY' => 'sst',
            default => 'vat',
        };

        $taxAuthority = match ($countryCode) {
            'NP' => 'Inland Revenue Department (IRD)',
            'IN' => 'GSTN / Central Board of Indirect Taxes and Customs',
            'MY' => 'Royal Malaysian Customs Department (JKDM)',
            'GB' => 'HM Revenue & Customs (HMRC)',
            'AE' => 'Federal Tax Authority (FTA)',
            'SA' => 'Zakat, Tax and Customs Authority (ZATCA)',
            'US' => 'State / Federal Department of Revenue',
            default => 'National Tax Authority',
        };

        $settings = TaxSetting::where('tenant_id', $tenantId)->first();

        if (! $settings) {
            $settings = TaxSetting::create([
                'tenant_id' => $tenantId,
                'country' => $countryName,
                'tax_regime' => $taxRegime,
                'tax_authority_name' => $taxAuthority,
                'reporting_frequency' => 'monthly',
                'accounting_method' => 'accrual',
                'default_pricing_mode' => 'exclusive',
                'rounding_level' => 'line',
                'rounding_precision' => 2,
                'rounding_direction' => 'half_up',
                'display_tax_summary_on_invoices' => true,
                'enable_einvoice_compliance' => false,
            ]);
        }

        // Auto-provision standard tax Chart of Accounts ledgers if missing
        $liabilityType = AccountType::where('tenant_id', $tenantId)->where('classification', AccountClassification::LIABILITY->value)->first()
            ?? AccountType::where('tenant_id', $tenantId)->where('code', 'CURR_LIAB')->first()
            ?? AccountType::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CURR_LIAB'],
                [
                    'name' => 'Current Liabilities',
                    'classification' => AccountClassification::LIABILITY->value,
                    'normal_balance' => AccountNormalBalance::CREDIT->value,
                    'financial_statement' => FinancialStatement::BALANCE_SHEET->value,
                    'financial_statement_section' => FinancialStatementSection::LIABILITIES->value,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

        $assetType = AccountType::where('tenant_id', $tenantId)->where('classification', AccountClassification::ASSET->value)->first()
            ?? AccountType::where('tenant_id', $tenantId)->where('code', 'CURR_ASSET')->first()
            ?? AccountType::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CURR_ASSET'],
                [
                    'name' => 'Current Assets',
                    'classification' => AccountClassification::ASSET->value,
                    'normal_balance' => AccountNormalBalance::DEBIT->value,
                    'financial_statement' => FinancialStatement::BALANCE_SHEET->value,
                    'financial_statement_section' => FinancialStatementSection::ASSETS->value,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

        $liabilityGroup = AccountGroup::where('tenant_id', $tenantId)->where('account_type_id', $liabilityType->id)->first()
            ?? AccountGroup::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GRP_CL'],
                [
                    'account_type_id' => $liabilityType->id,
                    'name' => 'Current Liabilities Group',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

        $assetGroup = AccountGroup::where('tenant_id', $tenantId)->where('account_type_id', $assetType->id)->first()
            ?? AccountGroup::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GRP_CA'],
                [
                    'account_type_id' => $assetType->id,
                    'name' => 'Current Assets Group',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

        $outputAccName = match ($taxRegime) {
            'gst' => 'GST Output (Sales Tax)',
            'sales_tax' => 'Sales Tax Output Payable',
            'sst' => 'SST Output (Sales Tax)',
            default => 'VAT Output (Sales Tax)',
        };

        $inputAccName = match ($taxRegime) {
            'gst' => 'Input Tax Credit (ITC - Purchases)',
            'sales_tax' => 'Purchase Tax (Recoverable)',
            'sst' => 'Purchase Tax Input',
            default => 'VAT Input (Purchase Tax)',
        };

        $withholdingAccName = match ($taxRegime) {
            'sales_tax' => 'Backup Withholding Tax Payable',
            default => 'TDS Payable (Withholding Tax)',
        };

        $outputTaxAcc = Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '2100'],
            [
                'account_type_id' => $liabilityType->id,
                'account_group_id' => $liabilityGroup->id,
                'name' => $outputAccName,
                'financial_statement_section' => FinancialStatementSection::LIABILITIES->value,
                'is_postable' => true,
                'is_control_account' => false,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $inputTaxAcc = Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '2110'],
            [
                'account_type_id' => $assetType->id,
                'account_group_id' => $assetGroup->id,
                'name' => $inputAccName,
                'financial_statement_section' => FinancialStatementSection::ASSETS->value,
                'is_postable' => true,
                'is_control_account' => false,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $tdsPayableAcc = Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '2120'],
            [
                'account_type_id' => $liabilityType->id,
                'account_group_id' => $liabilityGroup->id,
                'name' => $withholdingAccName,
                'financial_statement_section' => FinancialStatementSection::LIABILITIES->value,
                'is_postable' => true,
                'is_control_account' => false,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $taxSettlementAcc = Account::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => '2130'],
            [
                'account_type_id' => $liabilityType->id,
                'account_group_id' => $liabilityGroup->id,
                'name' => 'Tax Settlement / Clearing',
                'financial_statement_section' => FinancialStatementSection::LIABILITIES->value,
                'is_postable' => true,
                'is_control_account' => false,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        // Auto-select standard defaults if not yet mapped
        $settingsUpdated = false;
        if (! $settings->output_tax_account_id) {
            $settings->output_tax_account_id = $outputTaxAcc->id;
            $settingsUpdated = true;
        }
        if (! $settings->input_tax_account_id) {
            $settings->input_tax_account_id = $inputTaxAcc->id;
            $settingsUpdated = true;
        }
        if (! $settings->withholding_tax_account_id) {
            $settings->withholding_tax_account_id = $tdsPayableAcc->id;
            $settingsUpdated = true;
        }
        if (! $settings->tax_settlement_account_id) {
            $settings->tax_settlement_account_id = $taxSettlementAcc->id;
            $settingsUpdated = true;
        }
        if (! $settings->tax_regime) {
            $settings->tax_regime = $taxRegime;
            $settingsUpdated = true;
        }
        if (! $settings->tax_authority_name) {
            $settings->tax_authority_name = $taxAuthority;
            $settingsUpdated = true;
        }
        if ($settingsUpdated) {
            $settings->save();
        }

        $accounts = Account::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'financial_statement_section'])
            ->map(fn ($acc) => [
                'id' => $acc->id,
                'code' => $acc->code,
                'name' => $acc->name,
                'label' => "{$acc->code} - {$acc->name}",
                'section' => $acc->financial_statement_section?->value ?? 'general',
            ]);

        $taxRates = TaxRate::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'rate', 'rate_type', 'timeline_status']);

        $taxTypes = TaxType::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'scope']);

        return Inertia::render('Tax/Accounts/Index', [
            'settings' => [
                'output_tax_account_id' => $settings->output_tax_account_id,
                'input_tax_account_id' => $settings->input_tax_account_id,
                'withholding_tax_account_id' => $settings->withholding_tax_account_id,
                'tax_settlement_account_id' => $settings->tax_settlement_account_id,
                'tax_regime' => $settings->tax_regime,
                'country' => $settings->country,
            ],
            'accounts' => $accounts,
            'taxRates' => $taxRates,
            'taxTypes' => $taxTypes,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'output_tax_account_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
            'input_tax_account_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
            'withholding_tax_account_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
            'tax_settlement_account_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
        ]);

        $settings = TaxSetting::where('tenant_id', $tenantId)->firstOrFail();
        $settings->update($validated);

        return back()->with('success', 'Tax Chart of Accounts mappings saved successfully.');
    }
}
