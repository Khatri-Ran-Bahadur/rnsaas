<?php

namespace Modules\Payroll\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Payroll\Models\PayrollGroup;
use Modules\Payroll\Models\PayrollRun;
use Modules\Tenancy\Models\Tenant;

class PayrollDatabaseSeeder extends Seeder
{
    public function run(?int $targetTenantId = null): void
    {
        $tenants = $targetTenantId
            ? Tenant::where('id', $targetTenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            return;
        }

        foreach ($tenants as $tenant) {
            $groupCorp = PayrollGroup::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'PG-HQ-M'],
                [
                    'name' => 'HQ & Corporate Monthly',
                    'cycle' => 'monthly',
                    'pay_day' => 28,
                    'is_active' => true,
                ]
            );

            $groupRetail = PayrollGroup::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'PG-RET-W'],
                [
                    'name' => 'Retail & Restaurant Weekly',
                    'cycle' => 'weekly',
                    'pay_day' => 5,
                    'is_active' => true,
                ]
            );

            // Active & Past Runs
            PayrollRun::firstOrCreate(
                ['tenant_id' => $tenant->id, 'run_number' => 'PR-'.date('Y-m').'-M'],
                [
                    'payroll_group_id' => $groupCorp->id,
                    'period_name' => date('F Y').' (Monthly Standard)',
                    'start_date' => now()->startOfMonth()->toDateString(),
                    'end_date' => now()->endOfMonth()->toDateString(),
                    'pay_date' => now()->endOfMonth()->toDateString(),
                    'status' => 'calculated',
                    'total_employees' => 24,
                    'gross_amount' => 124500.00,
                    'deductions_amount' => 18400.00,
                    'net_amount' => 106100.00,
                    'employer_statutory' => 16185.00,
                    'created_by' => 'Ran Bahadur Khatri',
                ]
            );

            PayrollRun::firstOrCreate(
                ['tenant_id' => $tenant->id, 'run_number' => 'PR-'.date('Y-m', strtotime('-1 month')).'-M'],
                [
                    'payroll_group_id' => $groupCorp->id,
                    'period_name' => date('F Y', strtotime('-1 month')).' (Monthly Standard)',
                    'start_date' => now()->subMonth()->startOfMonth()->toDateString(),
                    'end_date' => now()->subMonth()->endOfMonth()->toDateString(),
                    'pay_date' => now()->subMonth()->endOfMonth()->toDateString(),
                    'status' => 'paid',
                    'total_employees' => 24,
                    'gross_amount' => 122000.00,
                    'deductions_amount' => 18100.00,
                    'net_amount' => 103900.00,
                    'employer_statutory' => 15860.00,
                    'created_by' => 'Ran Bahadur Khatri',
                    'approved_by' => 'Finance Director',
                ]
            );
        }
    }
}
