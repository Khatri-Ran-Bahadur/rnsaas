<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payroll\Models\PayrollRun;
use Modules\Tenancy\Models\Department;

class PayrollDashboardController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $runsQuery = PayrollRun::where('tenant_id', $tenantId)->with('group');
        $allRuns = $runsQuery->latest('created_at')->get();

        $activeRunModel = $allRuns->firstWhere('status', 'calculated') ?? $allRuns->firstWhere('status', 'draft') ?? $allRuns->first();
        $recentRuns = $allRuns->filter(fn ($r) => $r->status === 'paid')->take(4)->values()->map(function ($r) {
            return [
                'id' => $r->id,
                'run_number' => $r->run_number,
                'period_name' => $r->period_name,
                'payroll_group' => $r->group?->name ?? 'Standard Group',
                'pay_date' => $r->pay_date?->format('Y-m-d') ?? '',
                'total_employees' => (int) $r->total_employees,
                'net_amount' => (float) $r->net_amount,
                'status' => $r->status,
            ];
        })->toArray();

        $totalPayrollCost = (float) $allRuns->sum('gross_amount') + (float) $allRuns->sum('employer_statutory');
        $totalNetPay = (float) $allRuns->sum('net_amount');
        $totalStatutory = (float) $allRuns->sum('employer_statutory') + (float) $allRuns->sum('deductions_amount');

        $activeRunData = null;
        if ($activeRunModel) {
            $activeRunData = [
                'id' => $activeRunModel->id,
                'run_number' => $activeRunModel->run_number,
                'period_name' => $activeRunModel->period_name,
                'start_date' => $activeRunModel->start_date?->format('Y-m-d') ?? '',
                'end_date' => $activeRunModel->end_date?->format('Y-m-d') ?? '',
                'pay_date' => $activeRunModel->pay_date?->format('Y-m-d') ?? '',
                'status' => $activeRunModel->status,
                'payroll_group' => $activeRunModel->group?->name ?? 'Standard Group',
                'total_employees' => (int) $activeRunModel->total_employees,
                'gross_amount' => (float) $activeRunModel->gross_amount,
                'deductions_amount' => (float) $activeRunModel->deductions_amount,
                'net_amount' => (float) $activeRunModel->net_amount,
                'employer_statutory' => (float) $activeRunModel->employer_statutory,
                'cutoff_attendance' => now()->startOfMonth()->addDays(26)->format('Y-m-d'),
                'cutoff_overtime' => now()->startOfMonth()->addDays(26)->format('Y-m-d'),
                'approval_deadline' => now()->endOfMonth()->format('Y-m-d'),
            ];
        }

        $upcomingDeadlines = [
            [
                'event' => 'Attendance & OT Cutoff',
                'date' => now()->startOfMonth()->addDays(26)->format('Y-m-d'),
                'group' => 'HQ & Corporate',
                'type' => 'cutoff',
            ],
            [
                'event' => 'Payroll Approval Deadline',
                'date' => now()->endOfMonth()->format('Y-m-d'),
                'group' => 'Management Review',
                'type' => 'approval',
            ],
            [
                'event' => 'Direct Credit Salary Payout',
                'date' => now()->addMonth()->startOfMonth()->addDays(4)->format('Y-m-d'),
                'group' => 'Corporate Banking',
                'type' => 'payout',
            ],
            [
                'event' => 'Statutory Monthly Tax / Social Security Filing',
                'date' => now()->addMonth()->startOfMonth()->addDays(14)->format('Y-m-d'),
                'group' => 'Statutory Authority',
                'type' => 'compliance',
            ],
        ];

        $departments = Department::where('tenant_id', $tenantId)->withCount('staff')->get();
        $totalStaff = max(1, $departments->sum('staff_count'));
        $departmentBreakdown = $departments->map(function (Department $dept) use ($totalPayrollCost, $totalStaff): array {
            $share = round(($dept->staff_count / $totalStaff), 3);
            $amount = round($totalPayrollCost * $share, 2);

            return [
                'department' => $dept->name,
                'headcount' => (int) $dept->staff_count,
                'amount' => $amount,
                'pct' => round($share * 100, 1),
            ];
        })->toArray();

        return Inertia::render('Payroll/Dashboard', [
            'stats' => [
                'total_payroll_cost' => $totalPayrollCost,
                'total_net_pay' => $totalNetPay,
                'total_statutory_liability' => $totalStatutory,
                'total_active_employees' => $activeRunModel?->total_employees ?? 0,
                'pending_approvals_count' => $allRuns->whereIn('status', ['draft', 'calculated'])->count(),
                'active_run_status' => $activeRunModel?->status ?? 'none',
                'currency' => app(CurrentTenant::class)->has() ? (string) app(CurrentTenant::class)->get()->currency : 'USD',
                'currency_symbol' => match (strtoupper(app(CurrentTenant::class)->has() ? (string) app(CurrentTenant::class)->get()->currency : 'USD')) {
                    'USD' => '$',
                    'EUR' => '€',
                    'GBP' => '£',
                    'INR' => '₹',
                    'NPR' => 'रू',
                    'MYR' => 'RM',
                    default => app(CurrentTenant::class)->has() ? (string) app(CurrentTenant::class)->get()->currency : 'USD',
                },
            ],
            'activeRun' => $activeRunData,
            'recentRuns' => $recentRuns,
            'upcomingDeadlines' => $upcomingDeadlines,
            'departmentBreakdown' => $departmentBreakdown,
        ]);
    }
}
