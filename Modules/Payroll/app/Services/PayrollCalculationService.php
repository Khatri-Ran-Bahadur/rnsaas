<?php

namespace Modules\Payroll\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Overtime;
use Modules\Payroll\Models\EmployeeLoan;
use Modules\Payroll\Models\EmployeeSalaryProfile;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\Payslip;
use Modules\Payroll\Models\PayslipItem;
use Modules\Payroll\Models\StatutoryScheme;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

class PayrollCalculationService
{
    /**
     * Calculate and generate payslips for a given PayrollRun.
     */
    public function calculateForRun(PayrollRun $run): PayrollRun
    {
        $tenant = Tenant::find($run->tenant_id);
        if ($tenant) {
            $this->calculateRun($run, $tenant);
        }

        return $run->fresh();
    }

    /**
     * Calculate and generate payslips for a given PayrollRun with Tenant model.
     *
     * @return array{total_employees: int, gross_amount: float, deductions_amount: float, net_amount: float, employer_statutory: float, payslips: array<int, Payslip>}
     */
    public function calculateRun(PayrollRun $run, Tenant $tenant): array
    {
        $startDate = Carbon::parse($run->start_date)->startOfDay();
        $endDate = Carbon::parse($run->end_date)->endOfDay();
        $workingDays = max(1, $startDate->diffInDaysFiltered(fn (Carbon $date) => ! $date->isWeekend(), $endDate) ?: 30);

        // Fetch staff in scope
        $staffQuery = TenantStaff::query()
            ->where('tenant_id', $tenant->id)
            ->where('employment_status', EmploymentStatus::Active->value)
            ->with(['user', 'department', 'designation', 'branch']);

        $staffList = $staffQuery->get();

        $statutoryScheme = StatutoryScheme::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();

        $totalGross = 0;
        $totalDeductions = 0;
        $totalNet = 0;
        $totalEmployerStatutory = 0;
        $createdPayslips = [];

        // Remove previous draft payslips for recalculation
        Payslip::where('payroll_run_id', $run->id)->delete();

        foreach ($staffList as $index => $staff) {
            $salaryProfile = EmployeeSalaryProfile::query()
                ->where('tenant_id', $tenant->id)
                ->where('staff_id', $staff->id)
                ->first();

            $baseSalary = (float) ($salaryProfile?->base_salary ?? 45000.00);
            $hourlyRate = (float) ($salaryProfile?->hourly_rate ?? ($baseSalary / 208));

            // 1. Attendance & Loss of Pay (LOP)
            $attendanceRecords = Attendance::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $staff->user_id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $presentDays = (float) $attendanceRecords->whereIn('status', ['present', 'late', 'half_day'])->count();
            if ($attendanceRecords->isEmpty()) {
                // If attendance not logged daily, default to full working days
                $presentDays = (float) $workingDays;
                $absentDays = 0.0;
            } else {
                $absentDays = (float) max(0, $workingDays - $presentDays);
            }

            $perDayRate = $workingDays > 0 ? ($baseSalary / $workingDays) : 0;
            $lopDeduction = round($perDayRate * $absentDays, 2);

            // 2. Overtime calculation
            $approvedOvertimeHours = (float) Overtime::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $staff->user_id)
                ->where('status', 'approved')
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->sum('hours');

            $overtimeRate = $hourlyRate * 1.5;
            $overtimeEarnings = round($approvedOvertimeHours * $overtimeRate, 2);

            // 3. Custom / Standard Components
            $earningsList = [];
            $deductionsList = [];

            // Base Salary Line
            $earningsList[] = [
                'name' => 'Basic Salary',
                'code' => 'BASIC',
                'type' => 'earning',
                'amount' => $baseSalary,
                'is_taxable' => true,
            ];

            if ($overtimeEarnings > 0) {
                $earningsList[] = [
                    'name' => "Overtime ({$approvedOvertimeHours} hrs @ 1.5x)",
                    'code' => 'OVERTIME',
                    'type' => 'earning',
                    'amount' => $overtimeEarnings,
                    'is_taxable' => true,
                ];
            }

            // Custom allowances from profile
            $customComps = $salaryProfile?->custom_components ?? [];
            foreach ($customComps as $comp) {
                $amount = (float) ($comp['amount'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $type = $comp['type'] ?? 'earning';
                $item = [
                    'name' => $comp['name'] ?? 'Allowance',
                    'code' => $comp['code'] ?? Str::slug($comp['name'] ?? 'allowance', '_'),
                    'type' => $type,
                    'amount' => $amount,
                    'is_taxable' => (bool) ($comp['is_taxable'] ?? true),
                ];

                if ($type === 'earning') {
                    $earningsList[] = $item;
                } else {
                    $deductionsList[] = $item;
                }
            }

            if ($lopDeduction > 0) {
                $deductionsList[] = [
                    'name' => "Loss of Pay ({$absentDays} Absent Days)",
                    'code' => 'LOP',
                    'type' => 'deduction',
                    'amount' => $lopDeduction,
                    'is_taxable' => false,
                ];
            }

            // Total Gross
            $grossEarnings = array_sum(array_column($earningsList, 'amount')) - $lopDeduction;
            $grossEarnings = max(0, $grossEarnings);

            // 4. Statutory Deductions (SSF / PF / TDS Tax)
            $ssfEmployee = 0.0;
            $ssfEmployer = 0.0;
            $taxDeduction = 0.0;

            if ($statutoryScheme) {
                $ssfEmployeeRate = (float) $statutoryScheme->employee_rate ?: 11.0;
                $ssfEmployerRate = (float) $statutoryScheme->employer_rate ?: 20.0;
                $ssfEmployee = round(($baseSalary * $ssfEmployeeRate) / 100, 2);
                $ssfEmployer = round(($baseSalary * $ssfEmployerRate) / 100, 2);

                $taxDeduction = $this->calculateTaxWithholding($grossEarnings, $salaryProfile?->tax_status ?? 'single', $statutoryScheme);
            } else {
                // Default Nepal Statutory Presets (11% SSF, 20% Employer SSF, 1% SST TDS base)
                $ssfEmployee = round(($baseSalary * 0.11), 2);
                $ssfEmployer = round(($baseSalary * 0.20), 2);
                $taxDeduction = $this->calculateNepalTds($grossEarnings, $salaryProfile?->tax_status ?? 'single');
            }

            if ($ssfEmployee > 0) {
                $deductionsList[] = [
                    'name' => $statutoryScheme?->name ?? 'Social Security / Pension Fund',
                    'code' => 'SSF_EMP',
                    'type' => 'deduction',
                    'amount' => $ssfEmployee,
                    'is_taxable' => false,
                ];
            }

            if ($taxDeduction > 0) {
                $deductionsList[] = [
                    'name' => 'Payroll Withholding Tax (TDS / PAYE)',
                    'code' => 'TDS_TAX',
                    'type' => 'deduction',
                    'amount' => $taxDeduction,
                    'is_taxable' => false,
                ];
            }

            // 5. Loan / Salary Advance EMI Deductions
            $activeLoan = EmployeeLoan::query()
                ->where('tenant_id', $tenant->id)
                ->where('staff_id', $staff->id)
                ->where('status', 'active')
                ->where('remaining_amount', '>', 0)
                ->first();

            $loanDeduction = 0.0;
            if ($activeLoan) {
                $loanDeduction = min((float) $activeLoan->monthly_emi, (float) $activeLoan->remaining_amount);
                if ($loanDeduction > 0) {
                    $deductionsList[] = [
                        'name' => "Loan EMI ({$activeLoan->loan_number})",
                        'code' => 'LOAN_EMI',
                        'type' => 'deduction',
                        'amount' => $loanDeduction,
                        'is_taxable' => false,
                    ];
                }
            }

            $totalStaffDeductions = array_sum(array_column($deductionsList, 'amount'));
            $netPayable = max(0, $grossEarnings - $totalStaffDeductions);

            // Generate Payslip record
            $payslipNumber = sprintf('PS-%s-%04d', $run->run_number, $index + 1);

            $payslip = Payslip::create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'payroll_run_id' => $run->id,
                'staff_id' => $staff->id,
                'payslip_number' => $payslipNumber,
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
                'pay_date' => $run->pay_date,
                'base_salary' => $baseSalary,
                'gross_earnings' => $grossEarnings,
                'total_deductions' => $totalStaffDeductions,
                'net_payable' => $netPayable,
                'tax_deduction' => $taxDeduction,
                'ssf_employee_deduction' => $ssfEmployee,
                'ssf_employer_contribution' => $ssfEmployer,
                'loan_deductions' => $loanDeduction,
                'lop_deduction' => $lopDeduction,
                'overtime_earnings' => $overtimeEarnings,
                'working_days' => $workingDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'overtime_hours' => $approvedOvertimeHours,
                'payment_status' => 'unpaid',
                'payment_method' => $salaryProfile?->payment_method ?? 'bank_transfer',
            ]);

            // Save line items
            foreach ($earningsList as $item) {
                PayslipItem::create([
                    'tenant_id' => $tenant->id,
                    'payslip_id' => $payslip->id,
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'type' => 'earning',
                    'amount' => $item['amount'],
                    'is_taxable' => $item['is_taxable'],
                ]);
            }

            foreach ($deductionsList as $item) {
                PayslipItem::create([
                    'tenant_id' => $tenant->id,
                    'payslip_id' => $payslip->id,
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'type' => 'deduction',
                    'amount' => $item['amount'],
                    'is_taxable' => $item['is_taxable'],
                ]);
            }

            if ($ssfEmployer > 0) {
                PayslipItem::create([
                    'tenant_id' => $tenant->id,
                    'payslip_id' => $payslip->id,
                    'name' => 'Employer Statutory Contribution',
                    'code' => 'SSF_EMPLOYER',
                    'type' => 'employer_statutory',
                    'amount' => $ssfEmployer,
                    'is_taxable' => false,
                ]);
            }

            $totalGross += $grossEarnings;
            $totalDeductions += $totalStaffDeductions;
            $totalNet += $netPayable;
            $totalEmployerStatutory += $ssfEmployer;
            $createdPayslips[] = $payslip;
        }

        // Update PayrollRun record
        $run->update([
            'total_employees' => count($createdPayslips),
            'gross_amount' => $totalGross,
            'deductions_amount' => $totalDeductions,
            'net_amount' => $totalNet,
            'employer_statutory' => $totalEmployerStatutory,
            'status' => 'calculated',
        ]);

        return [
            'total_employees' => count($createdPayslips),
            'gross_amount' => $totalGross,
            'deductions_amount' => $totalDeductions,
            'net_amount' => $totalNet,
            'employer_statutory' => $totalEmployerStatutory,
            'payslips' => $createdPayslips,
        ];
    }

    /**
     * Compute Nepal TDS Income Tax withholding on monthly gross salary.
     */
    private function calculateNepalTds(float $monthlyGross, string $taxStatus): float
    {
        $annualIncome = $monthlyGross * 12;
        $isMarried = ($taxStatus === 'married');

        // Nepal Income Tax Slabs (Annual)
        // Single: 0-5L (1%), 5-7L (10%), 7-10L (20%), 10-20L (30%), 20L+ (36%)
        // Married: 0-6L (1%), 6-8L (10%), 8-11L (20%), 11-20L (30%), 20L+ (36%)
        $slab1 = $isMarried ? 600000 : 500000;
        $slab2 = $isMarried ? 800000 : 700000;
        $slab3 = $isMarried ? 1100000 : 1000000;
        $slab4 = 2000000;

        $annualTax = 0.0;

        if ($annualIncome <= $slab1) {
            $annualTax = $annualIncome * 0.01;
        } elseif ($annualIncome <= $slab2) {
            $annualTax = ($slab1 * 0.01) + (($annualIncome - $slab1) * 0.10);
        } elseif ($annualIncome <= $slab3) {
            $annualTax = ($slab1 * 0.01) + (($slab2 - $slab1) * 0.10) + (($annualIncome - $slab2) * 0.20);
        } elseif ($annualIncome <= $slab4) {
            $annualTax = ($slab1 * 0.01) + (($slab2 - $slab1) * 0.10) + (($slab3 - $slab2) * 0.20) + (($annualIncome - $slab3) * 0.30);
        } else {
            $annualTax = ($slab1 * 0.01) + (($slab2 - $slab1) * 0.10) + (($slab3 - $slab2) * 0.20) + (($slab4 - $slab3) * 0.30) + (($annualIncome - $slab4) * 0.36);
        }

        return round($annualTax / 12, 2);
    }

    /**
     * Compute custom statutory scheme tax withholding.
     */
    private function calculateTaxWithholding(float $monthlyGross, string $taxStatus, StatutoryScheme $scheme): float
    {
        $slabs = $scheme->tax_slabs;
        if (empty($slabs) || ! is_array($slabs)) {
            return $this->calculateNepalTds($monthlyGross, $taxStatus);
        }

        $annual = $monthlyGross * 12;
        $tax = 0.0;

        foreach ($slabs as $slab) {
            $from = (float) ($slab['from'] ?? 0);
            $to = isset($slab['to']) && $slab['to'] !== null ? (float) $slab['to'] : PHP_FLOAT_MAX;
            $rate = (float) ($slab['rate'] ?? 0) / 100;

            if ($annual > $from) {
                $taxableInSlab = min($annual, $to) - $from;
                $tax += ($taxableInSlab * $rate);
            }
        }

        return round($tax / 12, 2);
    }
}
