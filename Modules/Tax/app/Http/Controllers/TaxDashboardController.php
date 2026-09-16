<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Tax\Models\TaxExemption;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxRule;
use Modules\Tax\Models\TaxSetting;
use Modules\Tax\Models\TaxType;

class TaxDashboardController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $activeRatesCount = TaxRate::where('tenant_id', $tenantId)->where('timeline_status', 'active')->count();
        $taxTypesCount = TaxType::where('tenant_id', $tenantId)->count();
        $activeRulesCount = TaxRule::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $activeExemptionsCount = TaxExemption::where('tenant_id', $tenantId)->where('status', 'active')->count();

        $outputTaxMtd = (float) SalesInvoice::where('tenant_id', $tenantId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('tax_total');

        $inputTaxMtd = (float) PurchaseBill::where('tenant_id', $tenantId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('tax_total');

        $netTaxPayable = $outputTaxMtd - $inputTaxMtd;

        $settings = TaxSetting::where('tenant_id', $tenantId)->first();
        $filingFrequency = ucfirst($settings?->reporting_frequency ?? 'monthly');
        $currentRegime = $settings?->tax_regime ? strtoupper($settings->tax_regime).' Tax Engine' : 'Value Added Tax (VAT)';

        $stats = [
            'active_rates_count' => $activeRatesCount,
            'tax_types_count' => $taxTypesCount,
            'active_rules_count' => $activeRulesCount,
            'active_exemptions_count' => $activeExemptionsCount,
            'output_tax_mtd' => $outputTaxMtd,
            'input_tax_mtd' => $inputTaxMtd,
            'net_tax_payable' => $netTaxPayable,
            'next_filing_date' => now()->addMonth()->startOfMonth()->addDays(14)->toDateString(),
            'filing_frequency' => $filingFrequency,
            'current_regime' => $currentRegime,
        ];

        $recentRates = TaxRate::where('tenant_id', $tenantId)
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'action' => 'rate_updated',
                    'description' => "{$rate->name} ({$rate->code}) active at ".number_format((float) $rate->rate, 2).'%',
                    'user' => 'System Administrator',
                    'timestamp' => $rate->updated_at?->diffForHumans() ?? 'Recently',
                ];
            });

        $recentActivities = $recentRates->isEmpty() ? [
            [
                'id' => 1,
                'action' => 'system_ready',
                'description' => 'Tax Engine active and synchronized with General Ledger Chart of Accounts.',
                'user' => 'Tax Core',
                'timestamp' => 'Just now',
            ],
        ] : $recentRates->toArray();

        $upcomingFilings = [
            [
                'id' => 1,
                'period' => now()->format('F Y'),
                'due_date' => now()->endOfMonth()->toDateString(),
                'authority' => $settings?->tax_authority_name ?? 'Inland Revenue Department (IRD)',
                'status' => 'in_progress',
                'estimated_liability' => $netTaxPayable > 0 ? $netTaxPayable : 0.00,
            ],
        ];

        return Inertia::render('Tax/Dashboard', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'upcomingFilings' => $upcomingFilings,
        ]);
    }
}
