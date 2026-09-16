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
use Modules\Payroll\Models\EmployeeSalaryProfile;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\TenantStaff;

class EmployeePayrollController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = TenantStaff::where('tenant_id', $tenantId)
            ->with(['user', 'department', 'designation', 'salaryProfile.payrollGroup']);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('department')) {
            $departmentName = (string) $request->input('department');
            $query->whereHas('department', function ($dq) use ($departmentName): void {
                $dq->where('name', $departmentName);
            });
        }

        if ($request->filled('type')) {
            $query->where('employment_status', (string) $request->input('type'));
        }

        $paginator = $query->latest('id')->paginate(15)->withQueryString();

        $employeesData = collect($paginator->items())->map(function (TenantStaff $staff): array {
            $profile = $staff->salaryProfile;
            $baseSalary = (float) ($profile?->base_salary ?? 0.00);
            $allowances = 0.0;

            if ($profile && is_array($profile->custom_components)) {
                foreach ($profile->custom_components as $comp) {
                    if (($comp['type'] ?? '') === 'earning') {
                        $allowances += (float) ($comp['amount'] ?? 0);
                    }
                }
            }

            return [
                'id' => $staff->id,
                'name' => $staff->user?->name ?? 'Staff #'.$staff->id,
                'employee_code' => $staff->employee_code ?? ('EMP-'.str_pad((string) $staff->id, 3, '0', STR_PAD_LEFT)),
                'department' => $staff->department?->name ?? 'General',
                'designation' => $staff->designation?->name ?? 'Staff Member',
                'employment_type' => $staff->employment_type ?? 'full_time',
                'payroll_group' => $profile?->payrollGroup?->name ?? 'Default Monthly',
                'base_salary' => $baseSalary,
                'total_allowances' => $allowances,
                'monthly_gross' => $baseSalary + $allowances,
                'payment_method' => $profile?->payment_method ?? 'Bank Transfer',
                'status' => $staff->employment_status ?? 'active',
                'last_revision_date' => $profile?->updated_at?->format('Y-m-d') ?? $staff->created_at?->format('Y-m-d') ?? '',
            ];
        })->all();

        $departments = Department::where('tenant_id', $tenantId)->pluck('name')->toArray();
        if (empty($departments)) {
            $departments = ['Software Engineering', 'Accounting & Finance', 'Operations & Logistics', 'Sales & Marketing', 'Management'];
        }

        return Inertia::render('Payroll/Employees/Index', [
            'employees' => [
                'data' => $employeesData,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'department' => $request->input('department', ''),
                'type' => $request->input('type', ''),
            ],
            'departments' => $departments,
        ]);
    }

    public function show(int $id, Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $staff = TenantStaff::where('tenant_id', $tenantId)
            ->with(['user', 'department', 'designation', 'salaryProfile.payrollGroup'])
            ->findOrFail($id);

        $profile = $staff->salaryProfile;
        if (! $profile) {
            $defaultGroup = PayrollGroup::where('tenant_id', $tenantId)->first();
            $profile = EmployeeSalaryProfile::create([
                'tenant_id' => $tenantId,
                'staff_id' => $staff->id,
                'payroll_group_id' => $defaultGroup?->id,
                'wage_type' => 'monthly',
                'base_salary' => 45000.00,
                'currency' => app(CurrentTenant::class)->has() ? (string) app(CurrentTenant::class)->get()->currency : 'USD',
                'payment_method' => 'bank_transfer',
                'tax_status' => 'single',
                'custom_components' => [],
                'salary_history' => [],
                'is_active' => true,
            ]);
        }

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

        $allowances = [];
        $fixedDeductions = [];
        $totalAllowances = 0.0;

        if (is_array($profile->custom_components)) {
            foreach ($profile->custom_components as $idx => $comp) {
                if (($comp['type'] ?? '') === 'earning') {
                    $amt = (float) ($comp['amount'] ?? 0);
                    $totalAllowances += $amt;
                    $allowances[] = [
                        'id' => $idx + 1,
                        'name' => $comp['name'] ?? 'Allowance',
                        'amount' => $amt,
                        'taxable' => (bool) ($comp['taxable'] ?? true),
                        'epf_applicable' => (bool) ($comp['statutory'] ?? true),
                    ];
                } elseif (($comp['type'] ?? '') === 'deduction') {
                    $fixedDeductions[] = [
                        'id' => $idx + 1,
                        'name' => $comp['name'] ?? 'Fixed Deduction',
                        'amount' => (float) ($comp['amount'] ?? 0),
                        'remaining_balance' => 0.0,
                    ];
                }
            }
        }

        // Active loans
        $activeLoans = EmployeeLoan::where('tenant_id', $tenantId)
            ->where('staff_id', $staff->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeLoans as $l) {
            $fixedDeductions[] = [
                'id' => 1000 + $l->id,
                'name' => "Loan EMI: {$l->loan_type}",
                'amount' => (float) $l->monthly_installment,
                'remaining_balance' => (float) $l->remaining_balance,
            ];
        }

        $baseSalary = (float) $profile->base_salary;
        $totalGross = $baseSalary + $totalAllowances;

        $salaryHistory = is_array($profile->salary_history) && ! empty($profile->salary_history)
            ? $profile->salary_history
            : [
                [
                    'id' => 1,
                    'effective_date' => $profile->created_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                    'base_salary' => $baseSalary,
                    'total_gross' => $totalGross,
                    'increment_amount' => 0.00,
                    'increment_percentage' => '0.0%',
                    'reason' => 'Initial Joining Setup',
                    'approved_by' => 'HR Administration',
                    'created_at' => $profile->created_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                ],
            ];

        return Inertia::render('Payroll/Employees/Show', [
            'employee' => [
                'id' => $staff->id,
                'name' => $staff->user?->name ?? 'Staff #'.$staff->id,
                'employee_code' => $staff->employee_code ?? ('EMP-'.str_pad((string) $staff->id, 3, '0', STR_PAD_LEFT)),
                'nric_passport' => $profile->citizenship_number ?? 'N/A',
                'email' => $staff->user?->email ?? '',
                'phone' => $staff->phone ?? '',
                'department' => $staff->department?->name ?? 'General',
                'designation' => $staff->designation?->name ?? 'Staff Member',
                'employment_type' => $staff->employment_type ?? 'Full-Time Permanent',
                'joining_date' => $staff->joining_date?->format('Y-m-d') ?? $staff->created_at?->format('Y-m-d') ?? '',
                'payroll_group' => $profile->payrollGroup?->name ?? 'General Monthly',
                'currency' => $currency,
                'currency_symbol' => $currencySymbol,
                'status' => $staff->employment_status ?? 'active',
                'current_compensation' => [
                    'base_salary' => $baseSalary,
                    'pay_rate_type' => $profile->wage_type === 'hourly' ? 'hourly_rate' : 'monthly_fixed',
                    'effective_since' => $profile->updated_at?->format('Y-m-d') ?? '',
                    'allowances' => $allowances,
                    'fixed_deductions' => $fixedDeductions,
                    'total_gross_package' => $totalGross,
                ],
                'statutory_profile' => [
                    'epf_member_no' => $profile->ssf_number ?? $profile->pf_number ?? 'N/A',
                    'epf_employee_rate' => '11%',
                    'epf_employer_rate' => '20%',
                    'socso_member_no' => $profile->citizenship_number ?? 'N/A',
                    'tax_number' => $profile->pan_number ?? 'N/A',
                    'tax_residency' => 'Resident',
                    'marital_status' => ucfirst($profile->tax_status ?? 'single'),
                    'working_spouse' => false,
                    'children_count' => 0,
                ],
                'bank_details' => [
                    'bank_name' => $profile->bank_name ?? 'Primary Bank',
                    'account_number' => $profile->bank_account_number ?? 'N/A',
                    'account_holder' => $staff->user?->name ?? '',
                    'payment_method' => $profile->payment_method ?? 'bank_transfer',
                ],
                'salary_history' => $salaryHistory,
            ],
        ]);
    }

    public function storeRevision(int $id, Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $staff = TenantStaff::where('tenant_id', $tenantId)->findOrFail($id);

        $profile = EmployeeSalaryProfile::firstOrCreate(
            ['tenant_id' => $tenantId, 'staff_id' => $staff->id],
            ['base_salary' => 0, 'wage_type' => 'monthly', 'currency' => 'USD', 'payment_method' => 'bank_transfer']
        );

        $oldBase = (float) $profile->base_salary;
        $newBase = (float) ($request->input('base_salary') ?? $oldBase);
        $increment = $newBase - $oldBase;
        $pct = $oldBase > 0 ? round(($increment / $oldBase) * 100, 1).'%' : '0%';

        $history = is_array($profile->salary_history) ? $profile->salary_history : [];
        $history[] = [
            'id' => count($history) + 1,
            'effective_date' => $request->input('effective_date', now()->format('Y-m-d')),
            'base_salary' => $newBase,
            'total_gross' => $newBase,
            'increment_amount' => $increment,
            'increment_percentage' => $pct,
            'reason' => $request->input('reason', 'Salary adjustment / appraisal'),
            'approved_by' => $request->user()?->name ?? 'Executive Management',
            'created_at' => now()->format('Y-m-d'),
        ];

        $profile->update([
            'base_salary' => $newBase,
            'salary_history' => $history,
        ]);

        return redirect()->back()->with('success', 'New salary revision registered and saved successfully.');
    }
}
