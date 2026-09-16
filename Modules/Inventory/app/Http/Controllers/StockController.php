<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Models\InventoryStockAdjustment;

class StockController extends Controller
{
    use ResolvesCurrentTenantId;

    public function ledger(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = InventoryStockAdjustment::where('tenant_id', $tenantId)
            ->with(['item:id,name,sku,cost_price,on_hand_stock', 'location:id,name']);

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->input('item_id'));
        }

        $movements = $query->latest()->paginate(15)->withQueryString();

        $movements->through(function ($adj) {
            $type = $adj->type === 'addition' ? 'adjustment_in' : 'adjustment_out';
            $unitCost = (float) ($adj->item?->cost_price ?? 0);
            $qty = (float) $adj->quantity;

            return [
                'id' => $adj->id,
                'transaction_date' => $adj->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
                'reference' => 'ADJ-'.str_pad((string) $adj->id, 5, '0', STR_PAD_LEFT),
                'item_id' => $adj->item_id,
                'item_name' => $adj->item?->name ?? 'Inventory Product',
                'item_sku' => $adj->item?->sku ?? 'SKU-000',
                'location_name' => $adj->location?->name ?? 'HQ - Main Warehouse',
                'movement_type' => $type,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost * $qty,
                'balance_after' => (float) ($adj->item?->on_hand_stock ?? $qty),
                'user_name' => $adj->adjusted_by ?? 'Inventory Manager',
                'notes' => $adj->reason ?? 'Periodic physical stock audit',
            ];
        });

        $locations = InventoryLocation::where('tenant_id', $tenantId)->get(['id', 'name'])->toArray();

        return Inertia::render('Inventory/Stock/Ledger', [
            'movements' => $movements,
            'filters' => [
                'item_id' => $request->input('item_id', ''),
                'location_id' => $request->input('location_id', ''),
                'movement_type' => $request->input('movement_type', ''),
                'search' => $request->input('search', ''),
            ],
            'locations' => $locations,
        ]);
    }

    public function transfer(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $locations = InventoryLocation::where('tenant_id', $tenantId)->get(['id', 'name'])->toArray();
        $products = InventoryItem::where('tenant_id', $tenantId)
            ->get(['id', 'name', 'sku', 'on_hand_stock as on_hand', 'cost_price'])
            ->toArray();

        return Inertia::render('Inventory/Stock/Transfer', [
            'locations' => $locations,
            'products' => $products,
        ]);
    }

    public function adjustment(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $locations = InventoryLocation::where('tenant_id', $tenantId)->get(['id', 'name'])->toArray();
        $products = InventoryItem::where('tenant_id', $tenantId)
            ->get(['id', 'name', 'sku', 'on_hand_stock as on_hand', 'cost_price'])
            ->toArray();

        return Inertia::render('Inventory/Stock/Adjustment', [
            'locations' => $locations,
            'products' => $products,
            'initialItemId' => $request->input('item_id'),
        ]);
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'location_id' => 'required|integer',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer',
            'items.*.counted_qty' => 'required|numeric',
        ]);

        foreach ($validated['items'] as $itemData) {
            $item = InventoryItem::where('tenant_id', $tenantId)->find($itemData['item_id']);
            if ($item) {
                $diff = $itemData['counted_qty'] - $item->on_hand_stock;
                $type = $diff >= 0 ? 'addition' : 'reduction';

                InventoryStockAdjustment::create([
                    'tenant_id' => $tenantId,
                    'item_id' => $item->id,
                    'location_id' => $validated['location_id'],
                    'type' => $type,
                    'quantity' => abs($diff),
                    'reason' => $validated['notes'] ?? 'Manual stock count adjustment',
                    'adjusted_by' => $request->user()?->name ?? 'Inventory Controller',
                ]);

                $item->update(['on_hand_stock' => $itemData['counted_qty']]);
            }
        }

        return redirect()->route('admin.inventory.stock.ledger')->with('success', 'Stock adjustment posted successfully.');
    }
}
