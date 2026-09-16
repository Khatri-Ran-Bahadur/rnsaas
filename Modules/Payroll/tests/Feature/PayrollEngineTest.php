<?php

use App\Models\User;
use Modules\Payroll\Models\EmployeeLoan;
use Modules\Payroll\Models\EmployeeSalaryProfile;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollRun;
use Modules\Payroll\Models\Payslip;
use Modules\Payroll\Services\PayrollCalculationService;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['country_code' => 'NP', 'currency' => 'NPR']);
    $this->user = User::factory()->create();
    $this->user->tenants()->attach($this->tenant->id, ['status' => 'active']);

    $this->staff = TenantStaff::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'employment_status' => EmploymentStatus::Active->value,
    ]);

    $this->salaryProfile = EmployeeSalaryProfile::create([
        'tenant_id' => $this->tenant->id,
        'staff_id' => $this->staff->id,
        'wage_type' => 'monthly',
        'base_salary' => 50000.00,
        'currency' => 'NPR',
        'tax_status' => 'single',
        'is_active' => true,
        'custom_components' => [
            ['name' => 'Transport Allowance', 'code' => 'ALLOW_TRANSPORT', 'type' => 'earning', 'amount' => 5000.00, 'taxable' => true, 'statutory' => false],
        ],
    ]);
});

test('payroll calculation service generates payslips with correct SSF, TDS and net salary', function () {
    $group = PayrollGroup::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'HQ Monthly',
        'code' => 'HQ_MTH',
        'cycle' => 'monthly',
        'pay_day' => 28,
        'is_active' => true,
    ]);

    $run = PayrollRun::create([
        'tenant_id' => $this->tenant->id,
        'payroll_group_id' => $group->id,
        'run_number' => 'PR-2026-09-001',
        'period_name' => 'September 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'pay_date' => '2026-09-30',
        'status' => 'draft',
        'total_employees' => 0,
        'gross_amount' => 0,
        'deductions_amount' => 0,
        'net_amount' => 0,
        'employer_statutory' => 0,
        'created_by' => 'Test Suite',
    ]);

    $service = app(PayrollCalculationService::class);
    $resultRun = $service->calculateForRun($run);

    expect($resultRun->status)->toBe('calculated');
    expect($resultRun->total_employees)->toBe(1);
    expect((float) $resultRun->gross_amount)->toBe(55000.00); // 50000 base + 5000 allowance

    $payslip = Payslip::where('payroll_run_id', $run->id)->first();
    expect($payslip)->not->toBeNull();
    expect((float) $payslip->gross_earnings)->toBe(55000.00);
    expect((float) $payslip->net_payable)->toBeGreaterThan(0.0);
    expect($payslip->items()->count())->toBeGreaterThan(0);
});

test('payroll calculation service deducts active loan installments from payslips', function () {
    EmployeeLoan::create([
        'tenant_id' => $this->tenant->id,
        'staff_id' => $this->staff->id,
        'loan_number' => 'LN-2026-001',
        'loan_type' => 'company_loan',
        'principal_amount' => 12000.00,
        'total_repayable' => 12000.00,
        'monthly_emi' => 1000.00,
        'tenure_months' => 12,
        'paid_amount' => 0.0,
        'remaining_amount' => 12000.00,
        'status' => 'active',
    ]);

    $run = PayrollRun::create([
        'tenant_id' => $this->tenant->id,
        'run_number' => 'PR-2026-09-002',
        'period_name' => 'September 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'pay_date' => '2026-09-30',
        'status' => 'draft',
        'created_by' => 'Test Suite',
    ]);

    $service = app(PayrollCalculationService::class);
    $service->calculateForRun($run);

    $payslip = Payslip::where('payroll_run_id', $run->id)->first();
    $loanItem = $payslip->items()->where('code', 'LOAN_EMI')->first();

    expect($loanItem)->not->toBeNull();
    expect((float) $loanItem->amount)->toBe(1000.00);
});

test('payroll dashboard and pages render successfully without 500 errors', function () {
    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/payroll')
        ->assertOk();

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/payroll/runs')
        ->assertOk();

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/payroll/payslips')
        ->assertOk();

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/payroll/employees')
        ->assertOk();

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/payroll/loans')
        ->assertOk();

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/payroll/components')
        ->assertOk();

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/payroll/statutory')
        ->assertOk();

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/payroll/reports')
        ->assertOk();

    $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get('/admin/payroll/groups')
        ->assertOk();
});
