<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Models\InventoryItem;
use Modules\Tax\Models\TaxCategory;
use Modules\Tax\Models\TaxRate;

class TaxCategoryController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = TaxCategory::where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $standardRate = TaxRate::where('tenant_id', $tenantId)
            ->where('timeline_status', 'active')
            ->where('tax_category', 'standard')
            ->value('rate')
            ?? TaxRate::where('tenant_id', $tenantId)
                ->where('timeline_status', 'active')
                ->value('rate')
            ?? 0.00;

        $exemptItemsCount = InventoryItem::where('tenant_id', $tenantId)->where('tax_rate', '<=', 0)->count();
        $taxableItemsCount = InventoryItem::where('tenant_id', $tenantId)->where('tax_rate', '>', 0)->count();

        $categories = $query->orderBy('name')->get()->map(function ($cat) use ($standardRate, $exemptItemsCount, $taxableItemsCount) {
            $treatment = match ($cat->code) {
                'ZERO_RATED' => 'zero_rated',
                'EXEMPT' => 'exempt',
                default => 'taxable',
            };
            $rate = match ($cat->code) {
                'ZERO_RATED', 'EXEMPT' => '0.00%',
                default => number_format((float) $standardRate, 2).'%',
            };
            $itemsCount = match ($cat->code) {
                'EXEMPT' => $exemptItemsCount,
                'ZERO_RATED' => 0,
                default => $taxableItemsCount,
            };

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'code' => $cat->code,
                'treatment' => $treatment,
                'default_rate' => $rate,
                'claimable_input' => $cat->code !== 'EXEMPT',
                'description' => $cat->description,
                'items_count' => $itemsCount,
                'is_system' => true,
            ];
        });

        return Inertia::render('Tax/Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $request->input('search', ''),
                'treatment' => $request->input('treatment', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        TaxCategory::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'status' => $validated['status'] ?? 'active',
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Tax category created successfully.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $cat = TaxCategory::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $cat->update($validated);

        return back()->with('success', 'Tax category updated successfully.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $cat = TaxCategory::where('tenant_id', $tenantId)->findOrFail($id);
        $cat->delete();

        return back()->with('success', 'Tax category deleted successfully.');
    }
}
