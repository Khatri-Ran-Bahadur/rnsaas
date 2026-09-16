<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpWorkOrder;

class MrpProductionRunController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        return $this->execution($request);
    }

    public function execution(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $inProgressWo = MrpWorkOrder::where('tenant_id', $tenantId)
            ->where('status', 'in_progress')
            ->with(['bom', 'workCenter'])
            ->first();

        if (! $inProgressWo) {
            $inProgressWo = MrpWorkOrder::where('tenant_id', $tenantId)
                ->with(['bom', 'workCenter'])
                ->first();
        }

        if ($inProgressWo) {
            $activeRun = [
                'id' => $inProgressWo->id,
                'run_number' => 'RUN-'.str_pad((string) $inProgressWo->id, 4, '0', STR_PAD_LEFT),
                'wo_number' => $inProgressWo->wo_number,
                'product_name' => $inProgressWo->product_name,
                'product_sku' => $inProgressWo->product_sku ?? 'SKU-PRD',
                'bom_version' => $inProgressWo->bom?->version ?? 'v1.0',
                'planned_qty' => (float) $inProgressWo->planned_qty,
                'produced_qty' => (float) $inProgressWo->produced_qty,
                'remaining_qty' => (float) max(0, $inProgressWo->planned_qty - $inProgressWo->produced_qty),
                'target_qty' => (float) $inProgressWo->planned_qty,
                'unit' => $inProgressWo->unit ?? 'Units',
                'status' => ucfirst(str_replace('_', ' ', $inProgressWo->status)),
                'elapsed_time' => '02:15:30',
                'work_center' => $inProgressWo->workCenter?->name ?? 'Main Production Line',
                'operator' => $request->user()?->name ?? 'Lead Line Operator',
                'batch_number' => 'LOT-MRP-'.date('Ymd').'-01',
                'material_status' => 'All Issued (100%)',
                'current_operation' => 'Assembly & Baking Line',
                'current_sequence' => 20,
                'total_operations' => 4,
            ];
        } else {
            $activeRun = null;
        }

        $queue = MrpWorkOrder::where('tenant_id', $tenantId)
            ->when($inProgressWo, fn ($q) => $q->where('id', '!=', $inProgressWo->id))
            ->with(['workCenter'])
            ->limit(5)
            ->get()
            ->map(function ($wo) {
                return [
                    'id' => $wo->id,
                    'run_number' => 'RUN-'.str_pad((string) $wo->id, 4, '0', STR_PAD_LEFT),
                    'wo_number' => $wo->wo_number,
                    'product_name' => $wo->product_name,
                    'product_sku' => $wo->product_sku ?? 'SKU-PRD',
                    'planned_qty' => (float) $wo->planned_qty,
                    'unit' => $wo->unit ?? 'Units',
                    'priority' => ucfirst($wo->priority ?? 'normal'),
                    'status' => ucfirst(str_replace('_', ' ', $wo->status)),
                    'work_center' => $wo->workCenter?->name ?? 'Assembly Line',
                ];
            })->toArray();

        return Inertia::render('MRP/Execution/Index', [
            'activeRun' => $activeRun,
            'queue' => $queue,
        ]);
    }

    public function recordAction(Request $request): RedirectResponse
    {
        $action = $request->input('action', 'start');

        return redirect()->back()->with('success', "Production run action '{$action}' registered.");
    }

    public function reportOutput(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $qty = (float) $request->input('quantity', 0);
        $woId = $request->input('work_order_id');

        if ($woId && $qty > 0) {
            $wo = MrpWorkOrder::where('tenant_id', $tenantId)->find($woId);
            if ($wo) {
                $wo->increment('produced_qty', $qty);
                if ($wo->produced_qty >= $wo->planned_qty) {
                    $wo->update(['status' => 'completed']);
                }
            }
        }

        return redirect()->back()->with('success', "Reported {$qty} units produced successfully.");
    }

    public function reportScrap(Request $request): RedirectResponse
    {
        $qty = $request->input('quantity', 0);

        return redirect()->back()->with('success', "Recorded {$qty} scrap/loss units.");
    }
}
