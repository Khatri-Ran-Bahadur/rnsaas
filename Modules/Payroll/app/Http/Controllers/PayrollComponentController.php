<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payroll\Models\PayrollComponent;

class PayrollComponentController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $components = PayrollComponent::where('tenant_id', $tenantId)->get();

        if ($components->isEmpty()) {
            $defaults = [
                ['name' => 'Basic Salary', 'code' => 'BASIC', 'category' => 'earning', 'calculation_method' => 'fixed', 'is_taxable' => true, 'is_statutory' => true, 'is_active' => true],
                ['name' => 'Housing Allowance', 'code' => 'ALLOW_HOUSE', 'category' => 'allowance', 'calculation_method' => 'fixed', 'is_taxable' => true, 'is_statutory' => true, 'is_active' => true],
                ['name' => 'Transport Allowance', 'code' => 'ALLOW_TRANSPORT', 'category' => 'allowance', 'calculation_method' => 'fixed', 'is_taxable' => false, 'is_statutory' => false, 'is_active' => true],
                ['name' => 'Overtime (1.5x Hourly Rate)', 'code' => 'OT_NORMAL', 'category' => 'overtime', 'calculation_method' => 'formula', 'formula' => '(Basic / 176) * 1.5 * Hours', 'is_taxable' => true, 'is_statutory' => true, 'is_active' => true],
                ['name' => 'Performance Bonus', 'code' => 'BONUS', 'category' => 'bonus', 'calculation_method' => 'fixed', 'is_taxable' => true, 'is_statutory' => false, 'is_active' => true],
                ['name' => 'SSF / EPF Employee Contribution', 'code' => 'DED_SSF_EE', 'category' => 'statutory_deduction', 'calculation_method' => 'percentage_basic', 'default_percentage' => 11.0, 'is_taxable' => false, 'is_statutory' => true, 'is_active' => true],
                ['name' => 'Income Tax Withholding (TDS)', 'code' => 'DED_TDS', 'category' => 'tax_deduction', 'calculation_method' => 'statutory_engine', 'is_taxable' => false, 'is_statutory' => true, 'is_active' => true],
                ['name' => 'Loan / Advance Repayment', 'code' => 'DED_LOAN', 'category' => 'loan_deduction', 'calculation_method' => 'fixed', 'is_taxable' => false, 'is_statutory' => false, 'is_active' => true],
            ];

            foreach ($defaults as $item) {
                PayrollComponent::create(array_merge($item, ['tenant_id' => $tenantId]));
            }

            $components = PayrollComponent::where('tenant_id', $tenantId)->get();
        }

        $formattedComponents = $components->map(fn (PayrollComponent $c): array => [
            'id' => $c->id,
            'name' => $c->name,
            'code' => $c->code,
            'category' => $c->category,
            'calculation_method' => $c->calculation_method,
            'formula_text' => $c->formula,
            'default_percentage' => (float) $c->default_percentage,
            'default_amount' => (float) $c->default_amount,
            'is_taxable' => (bool) $c->is_taxable,
            'is_epf_applicable' => (bool) $c->is_statutory,
            'is_socso_applicable' => (bool) $c->is_statutory,
            'is_eis_applicable' => (bool) $c->is_statutory,
            'is_overtime_base' => in_array($c->code, ['BASIC', 'BASE_SALARY']),
            'is_leave_deduction_base' => in_array($c->code, ['BASIC', 'BASE_SALARY']),
            'gl_account_code' => $c->gl_account_code ?? '5100-01 (Salaries & Wages)',
            'status' => $c->is_active ? 'active' : 'inactive',
        ])->toArray();

        return Inertia::render('Payroll/Components/Index', [
            'components' => $formattedComponents,
            'categories' => [
                'earning' => 'Core Earnings',
                'allowance' => 'Allowances',
                'overtime' => 'Overtime',
                'commission' => 'Commissions',
                'bonus' => 'Bonuses',
                'statutory_deduction' => 'Employee Statutory Deductions',
                'tax_deduction' => 'Tax Withholdings',
                'loan_deduction' => 'Loan & Advance Recoveries',
                'employer_contribution' => 'Employer Statutory Contributions',
                'benefit' => 'Company Benefits',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        PayrollComponent::create([
            'tenant_id' => $tenantId,
            'name' => (string) $request->input('name'),
            'code' => strtoupper((string) ($request->input('code') ?: str_replace(' ', '_', (string) $request->input('name')))),
            'category' => (string) $request->input('category', 'earning'),
            'calculation_method' => (string) $request->input('calculation_method', 'fixed'),
            'default_amount' => (float) $request->input('default_amount', 0),
            'default_percentage' => (float) $request->input('default_percentage', 0),
            'formula' => $request->input('formula_text') ?: $request->input('formula'),
            'is_taxable' => (bool) $request->input('is_taxable', true),
            'is_statutory' => (bool) ($request->input('is_epf_applicable', true) || $request->input('is_statutory', true)),
            'is_active' => true,
            'gl_account_code' => $request->input('gl_account_code'),
        ]);

        return redirect()->back()->with('success', 'Payroll component registered successfully.');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $comp = PayrollComponent::where('tenant_id', $tenantId)->findOrFail($id);

        $comp->update([
            'name' => (string) $request->input('name', $comp->name),
            'code' => strtoupper((string) ($request->input('code') ?? $comp->code)),
            'category' => (string) $request->input('category', $comp->category),
            'calculation_method' => (string) $request->input('calculation_method', $comp->calculation_method),
            'default_amount' => (float) $request->input('default_amount', $comp->default_amount),
            'default_percentage' => (float) $request->input('default_percentage', $comp->default_percentage),
            'formula' => $request->input('formula_text') ?? $request->input('formula', $comp->formula),
            'is_taxable' => (bool) $request->input('is_taxable', $comp->is_taxable),
            'is_statutory' => (bool) $request->input('is_epf_applicable', $comp->is_statutory),
            'gl_account_code' => $request->input('gl_account_code', $comp->gl_account_code),
        ]);

        return redirect()->back()->with('success', 'Payroll component configuration updated.');
    }
}
