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
use Modules\Payroll\Models\PayrollGroup;

class PayrollGroupController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $groups = PayrollGroup::where('tenant_id', $tenantId)->get();

        if ($groups->isEmpty()) {
            $defaultGroup = PayrollGroup::create([
                'tenant_id' => $tenantId,
                'name' => 'HQ & Corporate Monthly',
                'code' => 'GRP_HQ_MONTHLY',
                'cycle' => 'monthly',
                'pay_day' => 28,
                'is_active' => true,
            ]);
            $groups = collect([$defaultGroup]);
        }

        $currency = app(CurrentTenant::class)->has() ? (string) app(CurrentTenant::class)->get()->currency : 'USD';

        $groupsData = $groups->map(function (PayrollGroup $g) use ($currency): array {
            $headcount = $g->salaryProfiles()->where('is_active', true)->count();

            return [
                'id' => $g->id,
                'name' => $g->name,
                'code' => $g->code,
                'frequency' => $g->cycle,
                'working_days_per_month' => $g->cycle === 'weekly' ? 26 : 22,
                'standard_daily_hours' => 8.0,
                'active_headcount' => $headcount,
                'currency' => $currency,
                'cutoff_attendance_days_before' => 3,
                'cutoff_overtime_days_before' => 3,
                'pay_day_of_month' => "Day {$g->pay_day} of month",
                'overtime_normal_multiplier' => 1.5,
                'overtime_restday_multiplier' => 2.0,
                'overtime_holiday_multiplier' => 3.0,
                'proration_method' => 'working_days',
                'status' => $g->is_active ? 'active' : 'inactive',
            ];
        })->toArray();

        return Inertia::render('Payroll/Groups/Index', [
            'groups' => $groupsData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $name = (string) $request->input('name', 'Corporate Monthly');
        $code = (string) ($request->input('code') ?: 'GRP_'.strtoupper(Str::slug($name, '_')));
        $cycle = (string) $request->input('frequency', $request->input('cycle', 'monthly'));
        $payDay = (int) $request->input('pay_day', 28);

        PayrollGroup::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => $code,
            'cycle' => $cycle,
            'pay_day' => $payDay,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Payroll group and pay calendar saved successfully.');
    }
}
