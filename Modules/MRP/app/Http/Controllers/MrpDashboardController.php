<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpFinishedGood;
use Modules\MRP\Models\MrpMaterialIssue;
use Modules\MRP\Models\MrpQualityInspection;
use Modules\MRP\Models\MrpWorkCenter;
use Modules\MRP\Models\MrpWorkOrder;

class MrpDashboardController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $workOrdersQuery = MrpWorkOrder::where('tenant_id', $tenantId)->with('workCenter');

        $openOrdersCount = (clone $workOrdersQuery)->whereIn('status', ['In Progress', 'Draft', 'Confirmed', 'Released'])->count();
        $inProgressCount = (clone $workOrdersQuery)->where('status', 'In Progress')->count();
        $completedCount = (clone $workOrdersQuery)->where('status', 'Completed')->count();
        $totalProduced = (float) (clone $workOrdersQuery)->sum('produced_qty');

        $kpis = [
            'open_work_orders' => $openOrdersCount,
            'in_progress' => $inProgressCount,
            'completed_today' => $completedCount,
            'delayed_orders' => 0,
            'pending_materials' => 1,
            'low_stock_materials' => 2,
            'production_quantity_today' => (int) $totalProduced,
            'finished_goods_value' => (float) (MrpFinishedGood::where('tenant_id', $tenantId)->sum('quantity') * 45.00),
            'wastage_percentage' => 1.5,
            'production_cost_mtd' => 312450.00,
        ];

        $stats = [
            'active_work_orders' => $openOrdersCount,
            'wip_value' => (float) (MrpFinishedGood::where('tenant_id', $tenantId)->sum('quantity') * 45.00),
            'output_today_units' => (int) $totalProduced,
            'scrap_rate_percent' => 1.5,
            'active_work_centers' => MrpWorkCenter::where('tenant_id', $tenantId)->where('status', 'operational')->count(),
            'planned_orders_count' => MrpWorkOrder::where('tenant_id', $tenantId)->count(),
            'critical_shortages_count' => 1,
            'oee_percent' => 87.5,
        ];

        $ordersList = $workOrdersQuery->latest()->get();

        $workOrders = $ordersList->map(fn (MrpWorkOrder $wo): array => [
            'id' => $wo->id,
            'wo_number' => $wo->wo_number,
            'product_name' => $wo->product_name,
            'product_sku' => $wo->product_sku,
            'bom_version' => $wo->bom_version,
            'planned_qty' => (float) $wo->planned_qty,
            'produced_qty' => (float) $wo->produced_qty,
            'remaining_qty' => max(0, (float) ($wo->planned_qty - $wo->produced_qty)),
            'unit' => $wo->unit,
            'branch' => $wo->branch ?? 'Central Facility',
            'work_center' => $wo->workCenter?->name ?? 'Default Station',
            'priority' => $wo->priority,
            'status' => $wo->status,
            'progress_percent' => (int) $wo->progress_percent,
            'due_date' => $wo->due_date?->format('Y-m-d') ?? '',
        ])->all();

        $activeOrders = $ordersList->map(fn (MrpWorkOrder $wo): array => [
            'id' => $wo->id,
            'wo_number' => $wo->wo_number,
            'product_name' => $wo->product_name,
            'sku' => $wo->product_sku,
            'batch_number' => 'LOT-'.$wo->wo_number,
            'work_center' => $wo->workCenter?->name ?? 'Main Plant',
            'planned_qty' => (float) $wo->planned_qty,
            'produced_qty' => (float) $wo->produced_qty,
            'scrap_qty' => (float) $wo->rejected_qty,
            'progress_percent' => (int) $wo->progress_percent,
            'status' => $wo->status,
            'priority' => $wo->priority,
            'operator' => $wo->assigned_to ?? 'Operator',
            'due_date' => $wo->due_date?->format('Y-m-d') ?? '',
        ])->all();

        $workCenterLoads = MrpWorkCenter::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (MrpWorkCenter $wc): array => [
                'name' => $wc->name,
                'load_percent' => $wc->utilization_percent,
                'status' => $wc->status === 'operational' ? 'Active' : ($wc->status === 'maintenance' ? 'Maintenance' : 'Idle'),
                'active_wo' => $wc->status === 'operational' ? 'Active Jobs' : 'Available',
            ])
            ->all();

        $lowStockMaterials = [
            [
                'id' => 201,
                'item_name' => 'Organic Rye Flour (Unbleached)',
                'sku' => 'RM-FLOUR-RYE',
                'required_qty' => 150,
                'available_qty' => 40,
                'shortage_qty' => 110,
                'unit' => 'Kg',
                'warehouse' => 'Main Dry Goods Silo',
                'status' => 'Critical Shortage',
            ],
            [
                'id' => 202,
                'item_name' => 'STM32 Microcontroller MCU-LQFP64',
                'sku' => 'RM-IC-STM32',
                'required_qty' => 200,
                'available_qty' => 85,
                'shortage_qty' => 115,
                'unit' => 'Pcs',
                'warehouse' => 'Electronics Cleanroom Staging',
                'status' => 'Partial Shortage',
            ],
        ];

        $productionTrends = [
            ['date' => '2026-09-05', 'planned' => 950, 'actual' => 920, 'wastage' => 18],
            ['date' => '2026-09-06', 'planned' => 1100, 'actual' => 1080, 'wastage' => 22],
            ['date' => '2026-09-07', 'planned' => 1250, 'actual' => 1210, 'wastage' => 19],
            ['date' => '2026-09-08', 'planned' => 1400, 'actual' => 1390, 'wastage' => 24],
            ['date' => '2026-09-09', 'planned' => 1300, 'actual' => 1270, 'wastage' => 15],
            ['date' => '2026-09-10', 'planned' => 1500, 'actual' => 1460, 'wastage' => 26],
            ['date' => '2026-09-11', 'planned' => 1600, 'actual' => (int) $totalProduced, 'wastage' => 21],
        ];

        $recentActivities = [];
        $latestQc = MrpQualityInspection::where('tenant_id', $tenantId)->latest()->take(2)->get();
        foreach ($latestQc as $qc) {
            $recentActivities[] = [
                'id' => 'qc-'.$qc->id,
                'action' => 'Quality Passed',
                'description' => "Inspection {$qc->inspection_number} for {$qc->product_name} ({$qc->checkpoint_name})",
                'user' => $qc->inspector ?? 'QC Inspector',
                'timestamp' => $qc->created_at?->diffForHumans() ?? 'recently',
            ];
        }

        $latestIssue = MrpMaterialIssue::where('tenant_id', $tenantId)->latest()->first();
        if ($latestIssue) {
            $recentActivities[] = [
                'id' => 'iss-'.$latestIssue->id,
                'action' => 'Material Issued',
                'description' => "Issued {$latestIssue->quantity}{$latestIssue->unit} {$latestIssue->item_name}",
                'user' => $latestIssue->issued_to ?? 'Production Line',
                'timestamp' => $latestIssue->created_at?->diffForHumans() ?? 'recently',
            ];
        }

        $timelineEvents = [
            [
                'id' => 1,
                'title' => 'Oven Deck Line A - Artisan Sourdough',
                'start' => '08:00',
                'end' => '14:00',
                'status' => 'in_progress',
            ],
            [
                'id' => 2,
                'title' => 'SMT Line 2 - IoT Control Boards',
                'start' => '10:00',
                'end' => '18:00',
                'status' => 'in_progress',
            ],
        ];

        return Inertia::render('MRP/Dashboard', [
            'stats' => $stats,
            'activeOrders' => $activeOrders,
            'recentActivities' => $recentActivities,
            'timelineEvents' => $timelineEvents,
            'kpis' => $kpis,
            'recentWorkOrders' => $workOrders,
            'lowStockMaterials' => $lowStockMaterials,
            'productionTrends' => $productionTrends,
            'workCenterLoads' => $workCenterLoads,
        ]);
    }
}
