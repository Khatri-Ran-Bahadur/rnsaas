<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\Payslip;

class PayrollReportController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $currentYear = now()->year;

        $ytdPayslips = Payslip::where('tenant_id', $tenantId)
            ->whereYear('created_at', $currentYear)
            ->with('items')
            ->get();

        $totalGrossYtd = (float) $ytdPayslips->sum('gross_salary');
        $totalNetPaidYtd = (float) $ytdPayslips->sum('net_salary');
        $totalTaxYtd = (float) $ytdPayslips->sum('tax_deduction');

        $totalSsfEmpYtd = (float) $ytdPayslips->sum(fn ($p) => $p->items->where('type', 'deduction')->whereIn('code', ['SSF_EMPLOYEE', 'EPF_EMPLOYEE', 'PF_EMPLOYEE'])->sum('amount'));
        $totalSsfEmprYtd = (float) $ytdPayslips->sum(fn ($p) => $p->items->where('type', 'employer_contribution')->whereIn('code', ['SSF_EMPLOYER', 'EPF_EMPLOYER', 'PF_EMPLOYER', 'GRATUITY_EMPLOYER'])->sum('amount'));

        $currency = app(CurrentTenant::class)->has() ? (string) app(CurrentTenant::class)->get()->currency : 'USD';
        $currencySymbol = match (strtoupper($currency)) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'NPR' => 'रू',
            'MYR' => 'RM',
            default => $currency,
        };

        $latestRun = PayrollRun::where('tenant_id', $tenantId)
            ->with('payslips.items')
            ->latest('id')
            ->first();

        $runPayslips = $latestRun ? $latestRun->payslips : collect();
        $headcount = $runPayslips->count();
        $latestPeriod = $latestRun?->period_name ?? now()->format('F Y');

        $runSsfEmp = (float) $runPayslips->sum(fn ($p) => $p->items->where('type', 'deduction')->whereIn('code', ['SSF_EMPLOYEE', 'EPF_EMPLOYEE', 'PF_EMPLOYEE'])->sum('amount'));
        $runSsfEmpr = (float) $runPayslips->sum(fn ($p) => $p->items->where('type', 'employer_contribution')->whereIn('code', ['SSF_EMPLOYER', 'EPF_EMPLOYER', 'PF_EMPLOYER', 'GRATUITY_EMPLOYER'])->sum('amount'));
        $runTax = (float) $runPayslips->sum('tax_deduction');

        $statutoryReports = [
            [
                'id' => 1,
                'name' => 'SSF / Provident Fund Monthly Contribution Schedule',
                'authority' => 'Social Security Fund / EPF Authority',
                'period' => $latestPeriod,
                'headcount' => $headcount,
                'total_employee' => $runSsfEmp,
                'total_employer' => $runSsfEmpr,
                'total_payable' => $runSsfEmp + $runSsfEmpr,
                'format' => 'CSV / Excel Upload Spec',
            ],
            [
                'id' => 2,
                'name' => 'Monthly Salary Withholding Tax Return (TDS / IRD)',
                'authority' => 'Tax Revenue / Inland Revenue Department',
                'period' => $latestPeriod,
                'headcount' => $headcount,
                'total_employee' => $runTax,
                'total_employer' => 0.00,
                'total_payable' => $runTax,
                'format' => 'Monthly e-TDS XML / Text File',
            ],
        ];

        $bankBatches = [
            [
                'id' => 1,
                'batch_ref' => 'BATCH-'.now()->format('Ym').'-01',
                'bank' => 'Corporate Bank Direct Payroll Transfer (NCHL / ACH / NEFT)',
                'total_records' => $headcount,
                'total_amount' => (float) ($latestRun?->net_amount ?? 0),
                'value_date' => $latestRun?->pay_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'status' => $latestRun?->status === 'paid' ? 'paid' : 'generated',
            ],
        ];

        return Inertia::render('Payroll/Reports/Index', [
            'summaryStats' => [
                'total_gross_ytd' => $totalGrossYtd,
                'total_net_paid_ytd' => $totalNetPaidYtd,
                'total_epf_ytd' => $totalSsfEmpYtd + $totalSsfEmprYtd,
                'total_socso_ytd' => 0.0,
                'total_eis_ytd' => 0.0,
                'total_tax_pcb_ytd' => $totalTaxYtd,
                'currency' => $currency,
                'currency_symbol' => $currencySymbol,
            ],
            'statutoryReports' => $statutoryReports,
            'bankBatches' => $bankBatches,
        ]);
    }
}
