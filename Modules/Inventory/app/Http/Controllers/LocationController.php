<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Tenancy\Models\Branch;

class LocationController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $locations = InventoryLocation::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(function ($loc) {
                return [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'code' => $loc->code ?? ('LOC-'.strtoupper(Str::slug($loc->name))),
                    'type' => in_array($loc->type, ['store', 'warehouse', 'kitchen', 'transit', 'returns']) ? $loc->type : 'warehouse',
                    'branch_name' => 'Main HQ Branch',
                    'address' => $loc->address ?? 'Main Logistics Facility',
                    'is_default' => $loc->id === 1,
                    'total_stock_count' => 140,
                    'status' => $loc->status === 'inactive' ? 'inactive' : 'active',
                ];
            });

        $branches = Branch::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Inventory/Locations/Index', [
            'locations' => $locations,
            'branches' => $branches,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'nullable|string|in:store,warehouse,kitchen,transit,returns',
            'address' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        InventoryLocation::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'code' => $validated['code'] ?: ('LOC-'.strtoupper(Str::slug($validated['name']))),
            'type' => $validated['type'] ?? 'warehouse',
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return back()->with('success', 'Location created successfully.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $location = InventoryLocation::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'nullable|string|in:store,warehouse,kitchen,transit,returns',
            'address' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $location->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?: $location->code,
            'type' => $validated['type'] ?? $location->type,
            'address' => $validated['address'] ?? $location->address,
            'status' => $validated['status'] ?? $location->status,
        ]);

        return back()->with('success', 'Location updated successfully.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $location = InventoryLocation::where('tenant_id', $tenantId)->findOrFail($id);
        $location->delete();

        return back()->with('success', 'Location deleted successfully.');
    }
}
