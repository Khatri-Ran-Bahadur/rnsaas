<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payroll\Models\Payslip;

class PayslipController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = Payslip::where('tenant_id', $tenantId)
            ->with(['staff.user', 'staff.department', 'staff.designation', 'payrollRun', 'salaryProfile']);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('payslip_number', 'like', "%{$search}%")
                    ->orWhereHas('staff.user', function ($uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('staff', function ($sq) use ($search): void {
                        $sq->where('employee_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('department')) {
            $departmentName = (string) $request->input('department');
            $query->whereHas('staff.department', function ($dq) use ($departmentName): void {
                $dq->where('name', $departmentName);
            });
        }

        if ($request->filled('period')) {
            $period = (string) $request->input('period');
            $query->whereHas('payrollRun', function ($rq) use ($period): void {
                $rq->where('period_name', 'like', "%{$period}%");
            });
        }

        $paginator = $query->latest('id')->paginate(15)->withQueryString();

        $currency = app(CurrentTenant::class)->has() ? (string) app(CurrentTenant::class)->get()->currency : 'USD';

        $payslipsData = collect($paginator->items())->map(function (Payslip $p) use ($currency): array {
            return [
                'id' => $p->id,
                'slip_number' => $p->payslip_number,
                'employee_id' => $p->staff_id,
                'employee_code' => $p->staff?->employee_code ?? ('EMP-'.str_pad((string) $p->staff_id, 3, '0', STR_PAD_LEFT)),
                'employee_name' => $p->staff?->user?->name ?? 'Staff #'.$p->staff_id,
                'department' => $p->staff?->department?->name ?? 'General',
                'designation' => $p->staff?->designation?->name ?? 'Staff Member',
                'period_name' => $p->payrollRun?->period_name ?? $p->period_start?->format('F Y'),
                'pay_date' => $p->payrollRun?->pay_date?->format('Y-m-d') ?? $p->pay_date?->format('Y-m-d') ?? '',
                'gross_pay' => (float) $p->gross_earnings,
                'deductions_total' => (float) $p->total_deductions,
                'net_pay' => (float) $p->net_payable,
                'currency' => $currency,
                'payment_status' => $p->payment_status,
                'payment_method' => $p->payment_method ?? $p->staff?->salaryProfile?->payment_method ?? 'Bank Transfer',
            ];
        })->all();

        return Inertia::render('Payroll/Payslips/Index', [
            'payslips' => [
                'data' => $payslipsData,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'department' => $request->input('department', ''),
                'period' => $request->input('period', ''),
            ],
        ]);
    }

    public function show(int $id, Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $payslip = Payslip::where('tenant_id', $tenantId)
            ->with(['staff.user', 'staff.department', 'staff.designation', 'payrollRun', 'salaryProfile', 'items'])
            ->findOrFail($id);

        $tenant = app(CurrentTenant::class)->has() ? app(CurrentTenant::class)->get() : null;
        $currency = $tenant ? (string) $tenant->currency : 'USD';
        $currencySymbol = match (strtoupper($currency)) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'NPR' => 'रू',
            'MYR' => 'RM',
            default => $currency,
        };

        $earnings = [];
        $deductions = [];
        $employerContributions = [];

        foreach ($payslip->items as $item) {
            if ($item->type === 'earning') {
                $earnings[] = [
                    'name' => $item->name,
                    'type' => $item->code === 'OVERTIME' ? 'overtime' : 'allowance',
                    'amount' => (float) $item->amount,
                ];
            } elseif ($item->type === 'deduction') {
                $deductions[] = [
                    'name' => $item->name,
                    'type' => str_contains($item->code, 'LOAN') ? 'loan' : 'statutory',
                    'amount' => (float) $item->amount,
                ];
            } elseif ($item->type === 'tax') {
                $deductions[] = [
                    'name' => $item->name,
                    'type' => 'tax',
                    'amount' => (float) $item->amount,
                ];
            } elseif ($item->type === 'employer_contribution') {
                $employerContributions[] = [
                    'name' => $item->name,
                    'amount' => (float) $item->amount,
                ];
            }
        }

        // Compute YTD for this employee in the same fiscal year
        $ytdPayslips = Payslip::where('tenant_id', $tenantId)
            ->where('staff_id', $payslip->staff_id)
            ->whereYear('created_at', $payslip->created_at?->year ?? now()->year)
            ->get();

        $ytdGross = (float) $ytdPayslips->sum('gross_earnings');
        $ytdNet = (float) $ytdPayslips->sum('net_payable');
        $ytdTax = (float) $ytdPayslips->sum('tax_deduction');

        return Inertia::render('Payroll/Payslips/Show', [
            'payslip' => [
                'id' => $payslip->id,
                'slip_number' => $payslip->payslip_number,
                'run_id' => $payslip->payroll_run_id,
                'run_number' => $payslip->payrollRun?->run_number ?? 'PR-DIRECT',
                'period_start' => $payslip->payrollRun?->start_date?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d'),
                'period_end' => $payslip->payrollRun?->end_date?->format('Y-m-d') ?? now()->endOfMonth()->format('Y-m-d'),
                'pay_date' => $payslip->payrollRun?->pay_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'company' => [
                    'name' => $tenant?->name ?? 'Enterprise Workspace',
                    'reg_no' => $tenant?->registration_number ?? 'REG-882190',
                    'employer_epf_no' => 'SSF-ORG-001',
                    'employer_socso_no' => 'PF-ORG-001',
                    'employer_tax_no' => $tenant?->tax_number ?? 'PAN-10928374',
                    'address' => $tenant?->address ?? 'Corporate Headquarters',
                    'currency' => $currency,
                    'currency_symbol' => $currencySymbol,
                ],
                'employee' => [
                    'id' => $payslip->staff_id,
                    'name' => $payslip->staff?->user?->name ?? 'Staff #'.$payslip->staff_id,
                    'employee_code' => $payslip->staff?->employee_code ?? ('EMP-'.str_pad((string) $payslip->staff_id, 3, '0', STR_PAD_LEFT)),
                    'nric_passport' => $payslip->staff?->salaryProfile?->citizenship_number ?? 'N/A',
                    'department' => $payslip->staff?->department?->name ?? 'General',
                    'designation' => $payslip->staff?->designation?->name ?? 'Staff Member',
                    'joining_date' => $payslip->staff?->joining_date?->format('Y-m-d') ?? '',
                    'employment_type' => $payslip->staff?->employment_type ?? 'Full-Time Permanent',
                    'epf_number' => $payslip->staff?->salaryProfile?->ssf_number ?? 'N/A',
                    'socso_number' => $payslip->staff?->salaryProfile?->pf_number ?? 'N/A',
                    'tax_number' => $payslip->staff?->salaryProfile?->pan_number ?? 'N/A',
                    'bank_name' => $payslip->staff?->salaryProfile?->bank_name ?? 'Primary Bank',
                    'bank_account' => $payslip->staff?->salaryProfile?->bank_account_number ?? 'N/A',
                    'payment_method' => $payslip->staff?->salaryProfile?->payment_method ?? 'Bank Transfer',
                ],
                'attendance_summary' => [
                    'working_days' => $payslip->working_days,
                    'days_worked' => $payslip->present_days,
                    'paid_leave_days' => $payslip->leave_days,
                    'unpaid_leave_days' => $payslip->absent_days,
                    'normal_ot_hours' => $payslip->overtime_hours,
                    'rest_day_ot_hours' => 0.0,
                    'holiday_ot_hours' => 0.0,
                ],
                'earnings' => $earnings,
                'deductions' => $deductions,
                'employer_contributions' => $employerContributions,
                'totals' => [
                    'gross_earnings' => (float) $payslip->gross_earnings,
                    'total_deductions' => (float) $payslip->total_deductions,
                    'net_pay' => (float) $payslip->net_payable,
                    'employer_total_statutory' => (float) $payslip->ssf_employer_contribution,
                    'total_cost_to_company' => (float) ($payslip->gross_earnings + $payslip->ssf_employer_contribution),
                ],
                'ytd_accumulators' => [
                    'ytd_gross_pay' => $ytdGross,
                    'ytd_epf_employee' => 0.0,
                    'ytd_epf_employer' => 0.0,
                    'ytd_socso_employee' => 0.0,
                    'ytd_eis_employee' => 0.0,
                    'ytd_tax_pcb' => $ytdTax,
                    'ytd_net_pay' => $ytdNet,
                ],
            ],
        ]);
    }
}
