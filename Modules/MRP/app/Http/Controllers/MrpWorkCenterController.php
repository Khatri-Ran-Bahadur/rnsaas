<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpWorkCenter;

class MrpWorkCenterController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $workCenters = MrpWorkCenter::where('tenant_id', $tenantId)
            ->with('workOrders')
            ->get()
            ->map(function (MrpWorkCenter $wc): array {
                $activeOrder = $wc->workOrders->where('status', 'In Progress')->first();

                return [
                    'id' => $wc->id,
                    'code' => $wc->code,
                    'name' => $wc->name,
                    'branch' => $wc->branch ?? 'Main Plant',
                    'type' => $wc->type,
                    'capacity_hours_per_day' => (float) $wc->capacity_hours_per_day,
                    'hourly_rate' => (float) $wc->hourly_rate,
                    'hourly_cost' => (float) $wc->hourly_cost,
                    'efficiency_percentage' => $wc->efficiency_percentage,
                    'oee_percentage' => $wc->oee_percentage,
                    'current_load_percentage' => $wc->utilization_percent,
                    'utilization_percent' => $wc->utilization_percent,
                    'active_jobs_count' => $wc->workOrders->where('status', 'In Progress')->count(),
                    'active_job_order' => $activeOrder ? "{$activeOrder->wo_number} ({$activeOrder->product_name})" : null,
                    'status' => $wc->status,
                    'machines_count' => $wc->machines_count,
                ];
            })
            ->all();

        $machines = collect($workCenters)->filter(fn ($wc) => $wc['type'] === 'machine')->values()->map(function ($wc, $idx) {
            return [
                'id' => $wc['id'],
                'code' => 'MCH-'.str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT),
                'name' => $wc['name'].' Primary Unit',
                'work_center' => $wc['name'],
                'status' => ucfirst($wc['status']),
                'health_percent' => (int) $wc['oee_percentage'],
                'last_maintenance' => now()->subMonth()->toDateString(),
                'next_maintenance' => now()->addMonths(2)->toDateString(),
            ];
        })->all();

        return Inertia::render('MRP/WorkCenters/Index', [
            'workCenters' => $workCenters,
            'machines' => $machines,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'capacity_hours_per_day' => ['nullable', 'numeric'],
            'hourly_rate' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string'],
        ]);

        MrpWorkCenter::create([
            'tenant_id' => $tenantId,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'branch' => $validated['branch'] ?? 'Main Plant',
            'type' => $validated['type'],
            'capacity_hours_per_day' => $validated['capacity_hours_per_day'] ?? 16.00,
            'hourly_rate' => $validated['hourly_rate'] ?? 45.00,
            'hourly_cost' => $validated['hourly_rate'] ?? 45.00,
            'efficiency_percentage' => 90,
            'oee_percentage' => 85,
            'utilization_percent' => 75,
            'status' => $validated['status'] ?? 'operational',
            'machines_count' => 1,
        ]);

        return redirect()->route('admin.mrp.work-centers.index')->with('success', 'Work center created successfully.');
    }
}
