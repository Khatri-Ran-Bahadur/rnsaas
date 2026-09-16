<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Models\InventoryStockAdjustment;

class DashboardController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $totalItems = InventoryItem::where('tenant_id', $tenantId)->count();
        $outOfStockCount = InventoryItem::where('tenant_id', $tenantId)->where('on_hand_stock', '<=', 0)->count();
        $lowStockQuery = InventoryItem::where('tenant_id', $tenantId)
            ->where('on_hand_stock', '>', 0)
            ->whereRaw('on_hand_stock <= reorder_point');
        $lowStockCount = $lowStockQuery->count();

        $totalValuation = (float) InventoryItem::where('tenant_id', $tenantId)
            ->selectRaw('SUM(on_hand_stock * cost_price) as val')
            ->value('val');

        $lowStockItems = $lowStockQuery
            ->with(['category', 'unit'])
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'on_hand' => (float) $item->on_hand_stock,
                    'reorder_point' => (float) $item->reorder_point,
                    'reorder_quantity' => (float) ($item->reorder_point * 2),
                    'unit_name' => $item->unit?->name ?? 'pcs',
                    'category_name' => $item->category?->name ?? 'General',
                ];
            });

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->withCount('items')
            ->get();

        $categoryBreakdown = $categories->map(function ($cat) use ($totalValuation) {
            $catValue = (float) $cat->items()->selectRaw('SUM(on_hand_stock * cost_price) as val')->value('val');
            $pct = $totalValuation > 0 ? round(($catValue / $totalValuation) * 100, 1) : 0;

            return [
                'name' => $cat->name,
                'items_count' => (int) $cat->items_count,
                'stock_value' => $catValue,
                'percentage' => $pct,
            ];
        });

        $locations = InventoryLocation::where('tenant_id', $tenantId)->get();
        $totalUnitsCount = (float) InventoryItem::where('tenant_id', $tenantId)->sum('on_hand_stock');
        $locCount = max(1, $locations->count());

        $locationBalances = $locations->map(function ($loc) use ($totalItems, $totalValuation, $totalUnitsCount, $locCount) {
            return [
                'id' => $loc->id,
                'name' => $loc->name,
                'items_count' => (int) round($totalItems / $locCount),
                'total_units' => (int) round($totalUnitsCount / $locCount),
                'valuation' => round($totalValuation / $locCount, 2),
            ];
        });

        $recentMovements = InventoryStockAdjustment::where('tenant_id', $tenantId)
            ->with('item:id,name')
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($adj) {
                return [
                    'id' => $adj->id,
                    'date' => $adj->created_at?->diffForHumans() ?? 'Just now',
                    'reference' => 'ADJ-'.str_pad((string) $adj->id, 5, '0', STR_PAD_LEFT),
                    'item_name' => $adj->item?->name ?? 'Stock Item',
                    'movement_type' => $adj->type === 'addition' ? 'Audit In' : 'Audit Out',
                    'quantity' => (float) $adj->quantity,
                ];
            });

        return Inertia::render('Inventory/Dashboard', [
            'stats' => [
                'total_valuation' => $totalValuation,
                'total_items' => $totalItems,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'turnover_rate' => 4.2,
            ],
            'lowStockItems' => $lowStockItems,
            'categoryBreakdown' => $categoryBreakdown,
            'locationBalances' => $locationBalances,
            'recentMovements' => $recentMovements,
        ]);
    }
}
