<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payroll\Models\EmployeeLoan;
use Modules\Tenancy\Models\TenantStaff;

class LoanAdvanceController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $paginator = EmployeeLoan::where('tenant_id', $tenantId)
            ->with(['staff.user', 'staff.department'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

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

        $loansData = collect($paginator->items())->map(function (EmployeeLoan $l): array {
            return [
                'id' => $l->id,
                'loan_number' => $l->loan_number,
                'type' => $l->loan_type === 'salary_advance' ? 'salary_advance' : 'company_loan',
                'employee_id' => $l->staff_id,
                'employee_code' => $l->staff?->employee_code ?? ('EMP-'.str_pad((string) $l->staff_id, 3, '0', STR_PAD_LEFT)),
                'employee_name' => $l->staff?->user?->name ?? 'Staff #'.$l->staff_id,
                'department' => $l->staff?->department?->name ?? 'General',
                'principal_amount' => (float) $l->principal_amount,
                'interest_rate' => (float) $l->interest_rate,
                'total_payable' => (float) $l->total_repayable,
                'monthly_installment' => (float) $l->monthly_emi,
                'total_installments' => (int) $l->tenure_months,
                'paid_installments' => (int) ($l->monthly_emi > 0 ? floor($l->paid_amount / $l->monthly_emi) : 0),
                'total_repaid' => (float) $l->paid_amount,
                'remaining_balance' => (float) $l->remaining_amount,
                'start_period' => $l->start_deduction_date?->format('Y-m-d') ?? $l->created_at?->format('Y-m-d') ?? '',
                'end_period' => $l->start_deduction_date?->addMonths($l->tenure_months)->format('Y-m-d') ?? '',
                'status' => $l->status,
                'reason' => $l->reason ?? 'Staff Financial Assistance Scheme',
                'approved_by' => $l->approved_by ?? 'Executive Management',
            ];
        })->all();

        $allLoans = EmployeeLoan::where('tenant_id', $tenantId)->get();
        $totalDisbursed = (float) $allLoans->sum('principal_amount');
        $totalRepaid = (float) $allLoans->sum('paid_amount');
        $outstanding = (float) $allLoans->where('status', 'active')->sum('remaining_amount');
        $activeCount = (int) $allLoans->where('status', 'active')->count();

        return Inertia::render('Payroll/Loans/Index', [
            'loans' => [
                'data' => $loansData,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'stats' => [
                'total_disbursed' => $totalDisbursed,
                'total_repaid' => $totalRepaid,
                'outstanding_balance' => $outstanding,
                'active_loans_count' => $activeCount,
                'currency' => $currency,
                'currency_symbol' => $currencySymbol,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $staffId = $request->input('employee_id');
        if (! $staffId && $request->filled('employee_name')) {
            $name = (string) $request->input('employee_name');
            $staff = TenantStaff::where('tenant_id', $tenantId)
                ->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$name}%"))
                ->first();
            $staffId = $staff?->id;
        }

        if (! $staffId) {
            $staff = TenantStaff::where('tenant_id', $tenantId)->first();
            $staffId = $staff?->id;
        }

        if (! $staffId) {
            return redirect()->back()->with('error', 'No valid employee found for this organization.');
        }

        $type = (string) $request->input('type', 'company_loan');
        $principal = (float) $request->input('principal_amount', 1000);
        $installments = max((int) $request->input('total_installments', 1), 1);
        $monthlyInstallment = (float) ($request->input('monthly_installment') ?? ($principal / $installments));

        $count = EmployeeLoan::where('tenant_id', $tenantId)->count() + 1;
        $prefix = $type === 'salary_advance' ? 'ADV' : 'LN';
        $loanNumber = "{$prefix}-".now()->format('Y').'-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        EmployeeLoan::create([
            'tenant_id' => $tenantId,
            'staff_id' => $staffId,
            'loan_number' => $loanNumber,
            'loan_type' => $type,
            'principal_amount' => $principal,
            'interest_rate' => 0.0,
            'total_repayable' => $principal,
            'monthly_emi' => $monthlyInstallment,
            'tenure_months' => $installments,
            'paid_amount' => 0.0,
            'remaining_amount' => $principal,
            'disbursed_at' => now()->toDateString(),
            'start_deduction_date' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
            'reason' => (string) $request->input('reason', 'Staff Financial Advance / Loan'),
        ]);

        return redirect()->back()->with('success', 'Employee loan / advance registered and installment schedule established.');
    }
}
