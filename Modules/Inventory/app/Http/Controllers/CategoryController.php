<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Models\InventoryCategory;

class CategoryController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $categories = InventoryCategory::where('tenant_id', $tenantId)
            ->with(['parent:id,name', 'items:id,category_id'])
            ->withCount('items')
            ->orderBy('name')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'code' => $cat->code ?? ('CAT-'.strtoupper(Str::slug($cat->name))),
                    'parent_id' => $cat->parent_id,
                    'parent' => $cat->parent ? ['id' => $cat->parent->id, 'name' => $cat->parent->name] : null,
                    'items_count' => (int) ($cat->items_count ?: $cat->item_count),
                    'description' => $cat->description,
                    'status' => $cat->status === 'inactive' ? 'inactive' : 'active',
                ];
            });

        $parentCategories = InventoryCategory::where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        return Inertia::render('Inventory/Categories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'parent_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        InventoryCategory::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'code' => $validated['code'] ?: ('CAT-'.strtoupper(Str::slug($validated['name']))),
            'parent_id' => ! empty($validated['parent_id']) ? $validated['parent_id'] : null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $category = InventoryCategory::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'parent_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'code' => $validated['code'] ?: $category->code,
            'parent_id' => ! empty($validated['parent_id']) ? $validated['parent_id'] : null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $category = InventoryCategory::where('tenant_id', $tenantId)->findOrFail($id);
        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}
