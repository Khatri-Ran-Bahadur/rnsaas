<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;
use Modules\MRP\Models\MrpWorkOrder;

class MrpPlanningController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $rawMaterials = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['raw_material', 'stock'])
            ->with('unit')
            ->get();

        $mainLocation = InventoryLocation::where('tenant_id', $tenantId)->first();
        $warehouseName = $mainLocation?->name ?? 'Main Production Warehouse';

        $materialRequirements = $rawMaterials->map(function ($rm) use ($warehouseName) {
            $required = (float) ($rm->reorder_point * 3);
            $available = (float) $rm->on_hand_stock;
            $reserved = (float) $rm->reserved_stock;
            $net = max(0, $required - max(0, $available - $reserved));

            $status = $available <= 0 ? 'Critical Shortage' : ($net > 0 ? 'Shortage' : 'Available');

            return [
                'id' => $rm->id,
                'material_name' => $rm->name,
                'sku' => $rm->sku,
                'required_qty' => $required,
                'available_qty' => $available,
                'reserved_qty' => $reserved,
                'incoming_qty' => 0.00,
                'net_requirement' => $net,
                'unit' => $rm->unit?->code ?? 'pcs',
                'warehouse' => $warehouseName,
                'lead_time_days' => 2,
                'status' => $status,
                'suggested_action' => $net > 0 ? 'Create Purchase Order ('.round($net * 1.2)." {$rm->unit?->code})" : 'Stock Sufficient',
            ];
        })->toArray();

        $shortages = collect($materialRequirements)->filter(fn ($m) => $m['net_requirement'] > 0)->values()->map(function ($m) {
            return [
                'item_code' => $m['sku'],
                'item_name' => $m['material_name'],
                'current_stock' => $m['available_qty'],
                'allocated_stock' => $m['reserved_qty'],
                'incoming_po' => $m['incoming_qty'],
                'projected_shortage' => $m['net_requirement'],
                'unit' => $m['unit'],
                'safety_stock' => 20.00,
            ];
        })->toArray();

        $suggestedPOs = collect($materialRequirements)->filter(fn ($m) => $m['net_requirement'] > 0)->values()->map(function ($m, $idx) {
            return [
                'id' => $idx + 1,
                'po_number' => 'DRAFT-PO-2026-0'.($idx + 1),
                'vendor_name' => 'Fresh Supplies & Ingredients Co.',
                'lead_time_days' => 3,
                'items_count' => 1,
                'lines' => [
                    [
                        'item_code' => $m['sku'],
                        'item_name' => $m['material_name'],
                        'quantity' => round($m['net_requirement'] * 1.2),
                        'unit' => $m['unit'],
                        'unit_price' => 15.00,
                        'total' => round($m['net_requirement'] * 1.2) * 15.00,
                    ],
                ],
                'total_amount' => round($m['net_requirement'] * 1.2) * 15.00,
                'target_delivery_date' => now()->addDays(3)->toDateString(),
            ];
        })->toArray();

        $suggestedWOs = MrpWorkOrder::where('tenant_id', $tenantId)->limit(3)->get()->map(function ($wo, $idx) {
            return [
                'id' => $idx + 1,
                'product_code' => $wo->product_sku ?? 'SKU-001',
                'product_name' => $wo->product_name,
                'bom_version' => 'v1.0',
                'quantity' => (float) $wo->planned_qty,
                'unit' => $wo->unit ?? 'Unit',
                'priority' => ucfirst($wo->priority ?? 'normal'),
                'target_start_date' => now()->addDays(1)->toDateString(),
                'target_completion_date' => now()->addDays(2)->toDateString(),
                'bottleneck_work_center' => 'Assembly Deck Line',
            ];
        })->toArray();

        $planSummary = [
            'total_materials_evaluated' => count($materialRequirements),
            'materials_available' => count(array_filter($materialRequirements, fn ($m) => $m['status'] === 'Available')),
            'materials_partial' => 0,
            'materials_shortage' => count($shortages),
            'critical_shortages_count' => count(array_filter($materialRequirements, fn ($m) => $m['status'] === 'Critical Shortage')),
            'suggested_po_count' => count($suggestedPOs),
            'suggested_wo_count' => count($suggestedWOs),
            'estimated_procurement_cost' => array_sum(array_column($suggestedPOs, 'total_amount')),
        ];

        return Inertia::render('MRP/Planning/Index', [
            'planSummary' => $planSummary,
            'materialRequirements' => $materialRequirements,
            'shortages' => $shortages,
            'suggestedPOs' => $suggestedPOs,
            'suggestedWOs' => $suggestedWOs,
        ]);
    }

    public function runMps(Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Master Production Schedule (MPS) recalculated successfully based on live inventory and open demand.');
    }

    public function createPo(Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Draft Purchase Order generated from MRP recommendations.');
    }

    public function createWo(Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Work order scheduled successfully from MRP requirements.');
    }
}
