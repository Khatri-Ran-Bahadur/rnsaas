<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Models\InventoryUnit;

class UnitController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $units = InventoryUnit::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                $category = match (strtolower($u->code)) {
                    'kg', 'g' => 'weight',
                    'l', 'ml' => 'volume',
                    'm', 'cm' => 'length',
                    'hrs', 'min' => 'time',
                    default => 'count',
                };

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'code' => $u->code,
                    'unit_category' => $category,
                    'is_base' => (bool) $u->is_base,
                    'precision' => in_array($category, ['weight', 'volume']) ? 3 : 0,
                ];
            });

        $conversions = [
            ['id' => 1, 'from_unit_name' => 'Carton (ctn)', 'to_unit_name' => 'Pieces (pcs)', 'conversion_factor' => 24, 'description' => '1 carton = 24 pcs'],
            ['id' => 2, 'from_unit_name' => 'Box (box)', 'to_unit_name' => 'Pieces (pcs)', 'conversion_factor' => 12, 'description' => '1 box = 12 pcs'],
            ['id' => 3, 'from_unit_name' => 'Kilogram (kg)', 'to_unit_name' => 'Gram (g)', 'conversion_factor' => 1000, 'description' => '1 kg = 1,000 g'],
            ['id' => 4, 'from_unit_name' => 'Litre (l)', 'to_unit_name' => 'Millilitre (ml)', 'conversion_factor' => 1000, 'description' => '1 L = 1,000 ml'],
        ];

        return Inertia::render('Inventory/Units/Index', [
            'units' => $units,
            'conversions' => $conversions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50',
            'unit_category' => 'nullable|string',
            'precision' => 'nullable|integer',
        ]);

        InventoryUnit::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'symbol' => $validated['code'],
            'is_base' => false,
            'status' => 'active',
        ]);

        return back()->with('success', 'Unit created successfully.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $unit = InventoryUnit::where('tenant_id', $tenantId)->findOrFail($id);
        $unit->delete();

        return back()->with('success', 'Unit deleted successfully.');
    }
}
