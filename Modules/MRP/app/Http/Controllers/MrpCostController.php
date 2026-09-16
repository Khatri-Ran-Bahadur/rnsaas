<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpWorkOrder;

class MrpCostController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $workOrders = MrpWorkOrder::where('tenant_id', $tenantId)->with('bom')->get();

        $costSheets = $workOrders->map(function ($wo) {
            $produced = max(1, (float) $wo->produced_qty);
            $totalCost = (float) $wo->total_cost;
            $estimatedCost = (float) ($wo->bom?->total_cost ?? $totalCost);
            if ($totalCost <= 0) {
                $totalCost = $estimatedCost;
            }

            $materialCost = round($totalCost * 0.65, 2);
            $laborCost = round($totalCost * 0.20, 2);
            $machineCost = round($totalCost * 0.10, 2);
            $overheadCost = round($totalCost * 0.05, 2);
            $unitCost = round($totalCost / $produced, 2);

            $diff = $totalCost - $estimatedCost;
            $varPct = $estimatedCost > 0 ? round(($diff / $estimatedCost) * 100, 2) : 0.00;
            $sign = $varPct > 0 ? "+{$varPct}%" : "{$varPct}%";
            $status = abs($varPct) < 5 ? 'Acceptable' : ($varPct > 0 ? 'Unfavorable' : 'Favorable');

            return [
                'id' => $wo->id,
                'wo_number' => $wo->wo_number,
                'product_name' => $wo->product_name,
                'sku' => $wo->product_sku ?? 'SKU-PRD',
                'planned_qty' => (float) $wo->planned_qty,
                'produced_qty' => (float) $wo->produced_qty,
                'material_cost' => $materialCost,
                'labor_cost' => $laborCost,
                'machine_cost' => $machineCost,
                'overhead_cost' => $overheadCost,
                'scrap_cost' => 0.00,
                'total_cost' => $totalCost,
                'estimated_unit_cost' => round($estimatedCost / max(1, (float) $wo->planned_qty), 2),
                'actual_unit_cost' => $unitCost,
                'variance_percent' => $sign,
                'variance_status' => $status,
            ];
        })->toArray();

        $avgVariance = ! empty($costSheets) ? round(collect($costSheets)->avg(fn ($c) => (float) str_replace(['%', '+'], '', $c['variance_percent'])), 1) : 0.0;
        $signAvg = $avgVariance > 0 ? "+{$avgVariance}%" : "{$avgVariance}%";

        $varianceBreakdown = [
            'material_price_variance' => $signAvg,
            'material_usage_variance' => '0.0%',
            'labor_rate_variance' => '0.0%',
            'labor_efficiency_variance' => '0.0%',
            'machine_cost_variance' => '0.0%',
            'overhead_variance' => '0.0%',
            'scrap_cost_variance' => '0.0%',
        ];

        return Inertia::render('MRP/Costs/Index', [
            'costSheets' => $costSheets,
            'varianceBreakdown' => $varianceBreakdown,
        ]);
    }
}
