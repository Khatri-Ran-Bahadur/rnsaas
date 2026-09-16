<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryUnit;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxSetting;
use Modules\Tenancy\Models\Branch;

class ItemController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = InventoryItem::where('tenant_id', $tenantId)
            ->with(['category:id,name', 'unit:id,name,code', 'branch:id,name,code']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('pos_available')) {
            $isPos = filter_var($request->input('pos_available'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_pos_available', $isPos);
        }

        $items = $query->latest()->paginate(15)->withQueryString();

        $items->through(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'type' => $item->type === 'raw_material' ? 'stock' : ($item->type ?? 'stock'),
                'category' => $item->category ? ['id' => $item->category->id, 'name' => $item->category->name] : null,
                'category_name' => $item->category?->name ?? 'Uncategorized',
                'brand' => $item->brand_name ? ['id' => $item->brand_name, 'name' => $item->brand_name] : null,
                'brand_name' => $item->brand_name,
                'branch_id' => $item->branch_id,
                'branch' => $item->branch ? ['id' => $item->branch->id, 'name' => $item->branch->name, 'code' => $item->branch->code] : null,
                'cost_price' => (float) $item->cost_price,
                'selling_price' => (float) $item->selling_price,
                'on_hand_stock' => (float) $item->on_hand_stock,
                'current_stock' => (float) $item->on_hand_stock,
                'available_stock' => (float) max(0, $item->on_hand_stock - $item->reserved_stock),
                'reserved_stock' => (float) $item->reserved_stock,
                'reorder_point' => (float) $item->reorder_point,
                'reorder_level' => (float) $item->reorder_point,
                'unit_name' => $item->unit?->name ?? 'pcs',
                'base_unit' => $item->unit?->code ?? 'pcs',
                'status' => $item->status ?? 'active',
                'is_pos_available' => (bool) $item->is_pos_available,
                'pos_enabled' => (bool) $item->is_pos_available,
                'is_favorite' => (bool) $item->is_favorite,
                'has_variants' => false,
                'has_recipe' => ! empty($item->modifier_groups),
                'track_stock' => (bool) $item->track_stock,
                'tax_rate' => (float) $item->tax_rate,
            ];
        });

        $totalItems = InventoryItem::where('tenant_id', $tenantId)->count();
        $stockItemsCount = InventoryItem::where('tenant_id', $tenantId)->whereIn('type', ['stock', 'raw_material'])->count();
        $lowStockCount = InventoryItem::where('tenant_id', $tenantId)
            ->whereRaw('on_hand_stock <= reorder_point')
            ->count();
        $totalValuation = (float) InventoryItem::where('tenant_id', $tenantId)
            ->selectRaw('SUM(on_hand_stock * cost_price) as val')
            ->value('val');

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $branches = Branch::where('tenant_id', $tenantId)->get(['id', 'name', 'code']);
        $brandSuggestions = InventoryItem::where('tenant_id', $tenantId)
            ->whereNotNull('brand_name')
            ->where('brand_name', '!=', '')
            ->distinct()
            ->pluck('brand_name');

        return Inertia::render('Inventory/Items/Index', [
            'items' => $items,
            'filters' => [
                'search' => $request->input('search', ''),
                'type' => $request->input('type', ''),
                'category_id' => $request->input('category_id', ''),
                'branch_id' => $request->input('branch_id', ''),
                'status' => $request->input('status', ''),
                'pos_available' => $request->input('pos_available', ''),
            ],
            'stats' => [
                'total_items' => $totalItems,
                'stock_items_count' => $stockItemsCount,
                'low_stock_count' => $lowStockCount,
                'total_inventory_value' => $totalValuation,
            ],
            'categories' => $categories,
            'brands' => $brandSuggestions->map(fn ($b) => ['id' => $b, 'name' => $b]),
            'branches' => $branches,
        ]);
    }

    public function create(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $categories = InventoryCategory::where('tenant_id', $tenantId)->get(['id', 'name', 'parent_id']);
        $units = InventoryUnit::where('tenant_id', $tenantId)->get(['id', 'name', 'code']);
        $taxRates = TaxRate::where('tenant_id', $tenantId)->get(['id', 'name', 'rate', 'code']);
        $branches = Branch::where('tenant_id', $tenantId)->get(['id', 'name', 'code']);
        $brandSuggestions = InventoryItem::where('tenant_id', $tenantId)
            ->whereNotNull('brand_name')
            ->where('brand_name', '!=', '')
            ->distinct()
            ->pluck('brand_name');

        $defaultTaxRate = 0.00;
        if (class_exists(TaxSetting::class)) {
            $taxSetting = TaxSetting::where('tenant_id', $tenantId)->with('salesTaxRate')->first();
            if ($taxSetting?->salesTaxRate) {
                $defaultTaxRate = (float) $taxSetting->salesTaxRate->rate;
            }
        }

        return Inertia::render('Inventory/Items/Create', [
            'categories' => $categories,
            'brands' => $brandSuggestions->map(fn ($b) => ['id' => $b, 'name' => $b]),
            'brandSuggestions' => $brandSuggestions,
            'branches' => $branches,
            'units' => $units,
            'taxRates' => $taxRates,
            'defaultTaxRate' => $defaultTaxRate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $defaultTaxRate = 0.00;
        if (class_exists(TaxSetting::class)) {
            $taxSetting = TaxSetting::where('tenant_id', $tenantId)->with('salesTaxRate')->first();
            if ($taxSetting?->salesTaxRate) {
                $defaultTaxRate = (float) $taxSetting->salesTaxRate->rate;
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer',
            'unit_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'brand_name' => 'nullable|string|max:100',
            'brand_id' => 'nullable|string|max:100',
            'type' => 'nullable|string',
            'selling_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'on_hand_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'is_pos_available' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|string',
        ]);

        $brandName = $validated['brand_name'] ?? ($validated['brand_id'] ?? null);

        InventoryItem::create([
            'tenant_id' => $tenantId,
            'branch_id' => $validated['branch_id'] ?? null,
            'name' => $validated['name'],
            'brand_name' => $brandName,
            'sku' => $validated['sku'],
            'barcode' => $validated['barcode'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'unit_id' => $validated['unit_id'] ?? null,
            'type' => $validated['type'] ?? 'stock',
            'selling_price' => $validated['selling_price'] ?? 0.00,
            'cost_price' => $validated['cost_price'] ?? 0.00,
            'on_hand_stock' => $validated['on_hand_stock'] ?? 0.00,
            'reorder_point' => $validated['reorder_point'] ?? 0.00,
            'is_pos_available' => $validated['is_pos_available'] ?? true,
            'is_favorite' => $validated['is_favorite'] ?? false,
            'track_stock' => $validated['track_stock'] ?? true,
            'tax_rate' => isset($validated['tax_rate']) ? (float) $validated['tax_rate'] : $defaultTaxRate,
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->route('admin.inventory.items.index')->with('success', 'Item created successfully.');
    }

    public function show(Request $request, $id): Response
    {
        $tenantId = $this->getTenantId($request);

        $item = InventoryItem::where('tenant_id', $tenantId)
            ->with(['category', 'unit', 'branch'])
            ->findOrFail($id);

        $itemData = [
            'id' => $item->id,
            'name' => $item->name,
            'brand_name' => $item->brand_name,
            'branch_id' => $item->branch_id,
            'branch' => $item->branch ? ['id' => $item->branch->id, 'name' => $item->branch->name] : null,
            'sku' => $item->sku,
            'barcode' => $item->barcode,
            'type' => $item->type === 'raw_material' ? 'stock' : ($item->type ?? 'stock'),
            'status' => $item->status ?? 'active',
            'category' => $item->category ? ['id' => $item->category->id, 'name' => $item->category->name] : null,
            'base_unit' => $item->unit ? ['id' => $item->unit->id, 'name' => $item->unit->name, 'code' => $item->unit->code] : null,
            'cost_price' => (float) $item->cost_price,
            'selling_price' => (float) $item->selling_price,
            'retail_price' => (float) $item->selling_price,
            'pos_price' => (float) $item->selling_price,
            'on_hand_stock' => (float) $item->on_hand_stock,
            'current_stock' => (float) $item->on_hand_stock,
            'available_stock' => (float) max(0, $item->on_hand_stock - $item->reserved_stock),
            'reserved_stock' => (float) $item->reserved_stock,
            'reorder_point' => (float) $item->reorder_point,
            'reorder_level' => (float) $item->reorder_point,
            'tax_type' => 'taxable',
            'tax_rate' => ['id' => 1, 'name' => 'Standard Rate', 'rate' => (float) $item->tax_rate],
            'is_pos_available' => (bool) $item->is_pos_available,
            'pos_enabled' => (bool) $item->is_pos_available,
            'is_favorite' => (bool) $item->is_favorite,
            'track_inventory' => (bool) $item->track_stock,
            'track_stock' => (bool) $item->track_stock,
            'locations' => [
                [
                    'location_name' => $item->branch ? $item->branch->name : 'HQ - Main Warehouse',
                    'on_hand' => (float) $item->on_hand_stock,
                    'reserved' => (float) $item->reserved_stock,
                    'available' => (float) max(0, $item->on_hand_stock - $item->reserved_stock),
                ],
            ],
        ];

        return Inertia::render('Inventory/Items/Show', [
            'item' => $itemData,
        ]);
    }

    public function edit(Request $request, $id): Response
    {
        $tenantId = $this->getTenantId($request);

        $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($id);
        $categories = InventoryCategory::where('tenant_id', $tenantId)->get(['id', 'name', 'parent_id']);
        $units = InventoryUnit::where('tenant_id', $tenantId)->get(['id', 'name', 'code']);
        $taxRates = TaxRate::where('tenant_id', $tenantId)->get(['id', 'name', 'rate', 'code']);
        $branches = Branch::where('tenant_id', $tenantId)->get(['id', 'name', 'code']);
        $brandSuggestions = InventoryItem::where('tenant_id', $tenantId)
            ->whereNotNull('brand_name')
            ->where('brand_name', '!=', '')
            ->distinct()
            ->pluck('brand_name');

        return Inertia::render('Inventory/Items/Create', [
            'item' => $item,
            'categories' => $categories,
            'brands' => $brandSuggestions->map(fn ($b) => ['id' => $b, 'name' => $b]),
            'brandSuggestions' => $brandSuggestions,
            'branches' => $branches,
            'units' => $units,
            'taxRates' => $taxRates,
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer',
            'unit_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'brand_name' => 'nullable|string|max:100',
            'brand_id' => 'nullable|string|max:100',
            'type' => 'nullable|string',
            'selling_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'on_hand_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'is_pos_available' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'tax_rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive,draft,archived',
        ]);

        if (isset($validated['brand_id']) && ! isset($validated['brand_name'])) {
            $validated['brand_name'] = $validated['brand_id'];
        }
        unset($validated['brand_id']);

        $item->update($validated);

        return back()->with('success', 'Item updated successfully.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($id);
        $item->delete();

        return redirect()->route('admin.inventory.items.index')->with('success', 'Item deleted successfully.');
    }
}
