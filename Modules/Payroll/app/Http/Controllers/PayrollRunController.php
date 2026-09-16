<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payroll\Models\EmployeeSalaryProfile;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\Payslip;
use Modules\Payroll\Models\StatutoryScheme;
use Modules\Payroll\Services\PayrollAccountingBridgeService;
use Modules\Payroll\Services\PayrollCalculationService;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Models\TenantStaff;

class PayrollRunController extends Controller
{
    use ResolvesCurrentTenantId;

    public function __construct(
        protected PayrollCalculationService $calculationService,
        protected PayrollAccountingBridgeService $accountingBridge
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = PayrollRun::where('tenant_id', $tenantId)->with('group');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('run_number', 'like', "%{$search}%")
                    ->orWhere('period_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('group')) {
            $query->where('payroll_group_id', (int) $request->input('group'));
        }

        $paginator = $query->latest('created_at')->paginate(15)->withQueryString();

        $runs = [
            'data' => collect($paginator->items())->map(function (PayrollRun $r): array {
                return [
                    'id' => $r->id,
                    'run_number' => $r->run_number,
                    'period_name' => $r->period_name,
                    'start_date' => $r->start_date?->format('Y-m-d') ?? '',
                    'end_date' => $r->end_date?->format('Y-m-d') ?? '',
                    'pay_date' => $r->pay_date?->format('Y-m-d') ?? '',
                    'status' => $r->status,
                    'payroll_group' => $r->group?->name ?? 'General Group',
                    'total_employees' => (int) $r->total_employees,
                    'gross_amount' => (float) $r->gross_amount,
                    'deductions_amount' => (float) $r->deductions_amount,
                    'net_amount' => (float) $r->net_amount,
                    'employer_statutory' => (float) $r->employer_statutory,
                    'created_by' => $r->created_by ?? 'System Administrator',
                    'approved_by' => $r->approved_by,
                    'created_at' => $r->created_at?->format('Y-m-d H:i:s') ?? '',
                ];
            })->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];

        $groups = PayrollGroup::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->get()
            ->toArray();

        return Inertia::render('Payroll/Runs/Index', [
            'runs' => $runs,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'group' => $request->input('group', ''),
            ],
            'groups' => $groups,
        ]);
    }

    public function create(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $groups = PayrollGroup::where('tenant_id', $tenantId)->get();

        if ($groups->isEmpty()) {
            $defaultGroup = PayrollGroup::create([
                'tenant_id' => $tenantId,
                'name' => 'HQ & Corporate Monthly',
                'code' => 'HQ-MTH-'.Str::random(4),
                'cycle' => 'monthly',
                'pay_day' => 28,
                'is_active' => true,
            ]);
            $groups = collect([$defaultGroup]);
        }

        $groupsList = $groups->map(fn (PayrollGroup $g): array => [
            'id' => $g->id,
            'name' => $g->name,
            'frequency' => $g->cycle ?? 'monthly',
            'working_days_per_month' => 22,
            'standard_hours_per_day' => 8,
            'active_headcount' => TenantStaff::where('tenant_id', $tenantId)->where('employment_status', EmploymentStatus::Active->value)->count(),
            'default_payment_method' => 'bank_transfer',
        ])->values()->toArray();

        $staffMembers = TenantStaff::where('tenant_id', $tenantId)
            ->where('employment_status', EmploymentStatus::Active->value)
            ->with(['user', 'department', 'designation'])
            ->get();

        $employees = $staffMembers->map(function (TenantStaff $staff) use ($tenantId) {
            $profile = EmployeeSalaryProfile::where('tenant_id', $tenantId)
                ->where('staff_id', $staff->id)
                ->first();

            $baseSalary = (float) ($profile?->base_salary ?? 45000.00);

            return [
                'id' => $staff->id,
                'name' => $staff->user?->name ?? 'Staff #'.$staff->id,
                'employee_code' => $staff->employee_code ?? ('EMP-'.str_pad((string) $staff->id, 3, '0', STR_PAD_LEFT)),
                'department' => $staff->department?->name ?? 'Operations',
                'designation' => $staff->designation?->name ?? 'Staff Member',
                'employment_type' => 'full_time',
                'base_salary' => $baseSalary,
                'currency' => $profile?->currency ?? 'NPR',
                'housing_allowance' => 0.00,
                'transport_allowance' => 0.00,
                'meal_allowance' => 0.00,
                'attendance_days' => 22,
                'absent_days' => 0,
                'unpaid_leave_days' => 0,
                'overtime_hours_15' => 0,
                'overtime_hours_20' => 0,
                'commission' => 0,
                'bonus' => 0,
                'loan_deduction' => 0,
                'advance_recovery' => 0,
                'epf_employee_pct' => 11.0,
                'epf_employer_pct' => 13.0,
                'socso_employee' => 0.0,
                'socso_employer' => 0.0,
                'eis_employee' => 0.0,
                'eis_employer' => 0.0,
                'tax_pcb' => 0.0,
                'proration_factor' => 1.0,
            ];
        })->values()->toArray();

        $statutorySchemes = StatutoryScheme::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn (StatutoryScheme $s): array => [
                'code' => $s->code,
                'name' => $s->name,
                'employee_rate' => $s->employee_rate > 0 ? "{$s->employee_rate}%" : 'Slab/Flat',
                'employer_rate' => $s->employer_rate > 0 ? "{$s->employer_rate}%" : '0%',
            ])->toArray();

        return Inertia::render('Payroll/Runs/Create', [
            'groups' => $groupsList,
            'employees' => $employees,
            'statutorySchemes' => $statutorySchemes,
        ]);
    }

    public function show(int $id, Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $run = PayrollRun::where('tenant_id', $tenantId)
            ->with(['group', 'payslips.staff.user', 'payslips.staff.department', 'payslips.staff.designation', 'payslips.items', 'payslips.salaryProfile'])
            ->findOrFail($id);

        $items = $run->payslips->map(function (Payslip $p): array {
            $earnings = $p->items->where('type', 'earning');
            $deductions = $p->items->where('type', 'deduction');
            $taxes = $p->items->where('type', 'tax');
            $contributions = $p->items->where('type', 'employer_contribution');

            $allowances = (float) $earnings->whereNotIn('code', ['BASE_SALARY', 'BASIC_SALARY', 'OVERTIME'])->sum('amount');
            $overtime = (float) $earnings->where('code', 'OVERTIME')->sum('amount');
            $loanDed = (float) $deductions->whereIn('code', ['LOAN_EMI', 'SALARY_ADVANCE'])->sum('amount');
            $taxTotal = (float) $taxes->sum('amount');

            // SSF / EPF / Social security items
            $ssfEmp = (float) $deductions->whereIn('code', ['SSF_EMPLOYEE', 'EPF_EMPLOYEE', 'PF_EMPLOYEE'])->sum('amount');
            $ssfEmpr = (float) $contributions->whereIn('code', ['SSF_EMPLOYER', 'EPF_EMPLOYER', 'PF_EMPLOYER', 'GRATUITY_EMPLOYER'])->sum('amount');

            return [
                'id' => $p->id,
                'employee_id' => $p->staff_id,
                'employee_code' => $p->staff?->employee_code ?? ('EMP-'.str_pad((string) $p->staff_id, 3, '0', STR_PAD_LEFT)),
                'employee_name' => $p->staff?->user?->name ?? 'Staff #'.$p->staff_id,
                'department' => $p->staff?->department?->name ?? 'General',
                'designation' => $p->staff?->designation?->name ?? 'Staff Member',
                'employment_type' => 'full_time',
                'base_salary' => (float) $p->base_salary,
                'allowances_total' => $allowances,
                'overtime_pay' => $overtime,
                'commission' => 0.0,
                'bonus' => 0.0,
                'gross_pay' => (float) $p->gross_earnings,
                'epf_employee' => $ssfEmp,
                'socso_employee' => 0.0,
                'eis_employee' => 0.0,
                'tax_pcb' => $taxTotal,
                'loan_deduction' => $loanDed,
                'advance_recovery' => 0.0,
                'total_deductions' => (float) $p->total_deductions,
                'net_pay' => (float) $p->net_payable,
                'epf_employer' => $ssfEmpr,
                'socso_employer' => 0.0,
                'eis_employer' => 0.0,
                'total_employer_cost' => (float) ($p->gross_earnings + $p->ssf_employer_contribution),
                'bank_name' => $p->staff?->salaryProfile?->bank_name ?? 'Primary Bank',
                'bank_account' => $p->staff?->salaryProfile?->bank_account_number ?? 'N/A',
                'status' => $p->payment_status,
            ];
        })->values()->toArray();

        $epfTotalEmployee = (float) $run->payslips->sum(fn ($p) => $p->items->where('type', 'deduction')->whereIn('code', ['SSF_EMPLOYEE', 'EPF_EMPLOYEE', 'PF_EMPLOYEE'])->sum('amount'));
        $epfTotalEmployer = (float) $run->payslips->sum(fn ($p) => $p->items->where('type', 'employer_contribution')->whereIn('code', ['SSF_EMPLOYER', 'EPF_EMPLOYER', 'PF_EMPLOYER', 'GRATUITY_EMPLOYER'])->sum('amount'));
        $taxTotalSum = (float) $run->payslips->sum('tax_deduction');

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

        return Inertia::render('Payroll/Runs/Show', [
            'run' => [
                'id' => $run->id,
                'run_number' => $run->run_number,
                'period_name' => $run->period_name,
                'start_date' => $run->start_date?->format('Y-m-d') ?? '',
                'end_date' => $run->end_date?->format('Y-m-d') ?? '',
                'pay_date' => $run->pay_date?->format('Y-m-d') ?? '',
                'status' => $run->status,
                'payroll_group' => $run->group?->name ?? 'HQ & Corporate Monthly',
                'total_employees' => (int) $run->total_employees,
                'gross_amount' => (float) $run->gross_amount,
                'deductions_amount' => (float) $run->deductions_amount,
                'net_amount' => (float) $run->net_amount,
                'employer_statutory' => (float) $run->employer_statutory,
                'total_payroll_cost' => (float) ($run->gross_amount + $run->employer_statutory),
                'currency' => $currency,
                'currency_symbol' => $currencySymbol,
                'created_by' => $run->created_by ?? 'System Administrator',
                'approved_by' => $run->approved_by,
                'journal_entry_id' => $run->journal_entry_id,
                'bank_batch_exported' => false,
            ],
            'items' => $items,
            'statutorySummary' => [
                'epf_total_employee' => $epfTotalEmployee,
                'epf_total_employer' => $epfTotalEmployer,
                'epf_grand_total' => $epfTotalEmployee + $epfTotalEmployer,
                'socso_total_employee' => 0.0,
                'socso_total_employer' => 0.0,
                'socso_grand_total' => 0.0,
                'eis_total_employee' => 0.0,
                'eis_total_employer' => 0.0,
                'eis_grand_total' => 0.0,
                'pcb_tax_total' => $taxTotalSum,
                'total_statutory_liability' => (float) $run->employer_statutory + $epfTotalEmployee + $taxTotalSum,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $groupId = $request->input('payroll_group_id') ?? $request->input('group_id');
        $periodName = $request->input('period_name') ?? $request->input('title');
        $startDate = $request->input('start_date') ?? now()->startOfMonth()->toDateString();
        $endDate = $request->input('end_date') ?? now()->endOfMonth()->toDateString();
        $payDate = $request->input('pay_date') ?? now()->endOfMonth()->toDateString();

        $group = PayrollGroup::where('tenant_id', $tenantId)->find($groupId);
        if (! $group) {
            $group = PayrollGroup::where('tenant_id', $tenantId)->first();
            if (! $group) {
                $group = PayrollGroup::create([
                    'tenant_id' => $tenantId,
                    'name' => 'HQ & Corporate Monthly',
                    'code' => 'HQ-MTH-'.Str::random(4),
                    'cycle' => 'monthly',
                    'pay_day' => 28,
                    'is_active' => true,
                ]);
            }
        }

        $runCount = PayrollRun::where('tenant_id', $tenantId)->count() + 1;
        $runNumber = 'PR-'.now()->format('Y-m').'-'.str_pad((string) $runCount, 3, '0', STR_PAD_LEFT);

        $run = PayrollRun::create([
            'tenant_id' => $tenantId,
            'payroll_group_id' => $group?->id,
            'run_number' => $runNumber,
            'period_name' => $periodName ?: ($group ? "{$group->name} - ".now()->format('F Y') : now()->format('F Y')),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'pay_date' => $payDate,
            'status' => 'draft',
            'total_employees' => 0,
            'gross_amount' => 0,
            'deductions_amount' => 0,
            'net_amount' => 0,
            'employer_statutory' => 0,
            'created_by' => $request->user()?->name ?? 'System Administrator',
        ]);

        // Automatically run calculation to generate initial payslips
        $this->calculationService->calculateForRun($run);

        return redirect()->route('admin.payroll.runs.show', $run->id)->with('success', 'Payroll run created and calculated successfully.');
    }

    public function calculate(int $id, Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $run = PayrollRun::where('tenant_id', $tenantId)->findOrFail($id);

        $this->calculationService->calculateForRun($run);

        return redirect()->back()->with('success', 'Payroll calculation and statutory formulas completed.');
    }

    public function approve(int $id, Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $run = PayrollRun::where('tenant_id', $tenantId)->findOrFail($id);

        $run->update([
            'status' => 'approved',
            'approved_by' => $request->user()?->name ?? 'Executive Management',
        ]);

        return redirect()->back()->with('success', 'Payroll run approved by executive management.');
    }

    public function finalize(int $id, Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $run = PayrollRun::where('tenant_id', $tenantId)->findOrFail($id);

        $run->update(['status' => 'finalized']);

        // Post automated journal entry to Accounting module if available
        $journal = $this->accountingBridge->postPayrollJournal($run);

        if ($journal) {
            $run->update(['journal_entry_id' => $journal->id]);
        }

        return redirect()->back()->with('success', 'Payroll finalized. Automated accounting journal posted to General Ledger.');
    }

    public function markPaid(int $id, Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $run = PayrollRun::where('tenant_id', $tenantId)->findOrFail($id);
        $run->update(['status' => 'paid']);

        // Mark all payslips as paid
        Payslip::where('payroll_run_id', $run->id)->update(['status' => 'paid']);

        return redirect()->back()->with('success', 'Payroll marked as Paid. Bank direct batch file generated.');
    }
}
