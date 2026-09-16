<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpBom;
use Modules\MRP\Models\MrpBomItem;

class MrpBomController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $boms = MrpBom::where('tenant_id', $tenantId)
            ->with('items')
            ->latest()
            ->get()
            ->map(function (MrpBom $bom): array {
                $components = $bom->items->map(fn (MrpBomItem $item): array => [
                    'id' => (string) $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'unit_cost' => (float) $item->unit_cost,
                    'is_subassembly' => (bool) $item->is_subassembly,
                ])->all();

                return [
                    'id' => $bom->id,
                    'bom_number' => $bom->bom_number,
                    'product_name' => $bom->product_name,
                    'item_name' => $bom->product_name,
                    'sku' => $bom->sku,
                    'item_sku' => $bom->sku,
                    'bom_type' => $bom->bom_type,
                    'version' => $bom->version,
                    'output_qty' => (float) $bom->output_qty,
                    'quantity' => (float) $bom->output_qty,
                    'unit' => $bom->unit,
                    'estimated_cost' => (float) $bom->estimated_cost,
                    'total_cost' => (float) $bom->estimated_cost,
                    'unit_cost' => (float) $bom->unit_cost,
                    'status' => $bom->status,
                    'is_active' => (bool) $bom->is_active,
                    'is_default' => (bool) $bom->is_default,
                    'routing_name' => $bom->routing_name ?? 'Standard Production Routing',
                    'total_components_count' => count($components),
                    'components_count' => count($components),
                    'last_updated' => $bom->updated_at?->format('Y-m-d') ?? '',
                    'effective_from' => $bom->effective_from?->format('Y-m-d') ?? '',
                    'effective_to' => $bom->effective_to?->format('Y-m-d'),
                    'levels_count' => (int) $bom->levels_count,
                    'created_by' => $bom->created_by ?? 'Engineering',
                    'hierarchy' => [
                        'id' => 'root-'.$bom->id,
                        'name' => $bom->product_name,
                        'sku' => $bom->sku,
                        'quantity' => (float) $bom->output_qty,
                        'unit' => $bom->unit,
                        'unit_cost' => (float) $bom->unit_cost,
                        'children' => $components,
                    ],
                ];
            })
            ->all();

        return Inertia::render('MRP/BOM/Index', [
            'boms' => $boms,
        ]);
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = $this->getTenantId($request);

        $bom = MrpBom::where('tenant_id', $tenantId)
            ->with('items')
            ->findOrFail($id);

        $materialCost = $bom->items->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_cost);

        $bomData = [
            'id' => $bom->id,
            'bom_number' => $bom->bom_number,
            'product_name' => $bom->product_name,
            'item_name' => $bom->product_name,
            'sku' => $bom->sku,
            'item_sku' => $bom->sku,
            'bom_type' => $bom->bom_type,
            'version' => $bom->version,
            'status' => $bom->status,
            'is_active' => (bool) $bom->is_active,
            'quantity' => (float) $bom->output_qty,
            'unit' => $bom->unit,
            'effective_from' => $bom->effective_from?->format('Y-m-d') ?? '',
            'effective_to' => $bom->effective_to?->format('Y-m-d'),
            'created_by' => $bom->created_by ?? 'Lead Engineer',
            'approved_by' => 'Operations Manager',
            'description' => $bom->notes ?? 'Production Recipe & Assembly Bill of Materials.',
            'costing' => [
                'material_cost' => round($materialCost, 2),
                'labor_cost' => round($materialCost * 0.15, 2),
                'machine_cost' => round($materialCost * 0.08, 2),
                'overhead_cost' => round($materialCost * 0.06, 2),
                'scrap_cost' => round($materialCost * 0.02, 2),
                'total_cost' => round($materialCost * 1.31, 2),
                'cost_per_unit' => round(($materialCost * 1.31) / max(1, (float) $bom->output_qty), 2),
            ],
            'versions' => [
                ['version' => $bom->version, 'effective_from' => $bom->effective_from?->format('Y-m-d') ?? '', 'status' => $bom->status, 'cost' => (float) $bom->estimated_cost, 'author' => $bom->created_by ?? 'Engineering'],
            ],
            'tree' => [
                'id' => 'root',
                'name' => $bom->product_name,
                'sku' => $bom->sku,
                'quantity' => (float) $bom->output_qty,
                'unit' => $bom->unit,
                'cost' => (float) $bom->estimated_cost,
                'children' => $bom->items->map(fn (MrpBomItem $item): array => [
                    'id' => 'item-'.$item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'unit_cost' => (float) $item->unit_cost,
                    'scrap_percent' => (float) $item->scrap_percentage,
                    'warehouse' => 'Main Warehouse',
                ])->all(),
            ],
        ];

        return Inertia::render('MRP/BOM/Show', [
            'bom' => $bomData,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('MRP/BOM/Create');
    }

    public function edit(Request $request, int|string $id): Response
    {
        $tenantId = $this->getTenantId($request);

        $bom = MrpBom::where('tenant_id', $tenantId)
            ->with('items')
            ->findOrFail($id);

        return Inertia::render('MRP/BOM/Edit', [
            'id' => $id,
            'bom' => $bom,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'bom_number' => ['nullable', 'string', 'max:50'],
            'product_name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:50'],
            'bom_type' => ['nullable', 'string'],
            'version' => ['nullable', 'string'],
            'output_qty' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string'],
            'components' => ['nullable', 'array'],
        ]);

        $bomCount = MrpBom::where('tenant_id', $tenantId)->count();
        $bomNumber = $validated['bom_number'] ?? ('BOM-'.date('Y').'-'.sprintf('%03d', $bomCount + 1));

        $bom = MrpBom::create([
            'tenant_id' => $tenantId,
            'bom_number' => $bomNumber,
            'product_name' => $validated['product_name'],
            'sku' => $validated['sku'],
            'bom_type' => $validated['bom_type'] ?? 'Production BOM',
            'version' => $validated['version'] ?? 'v1.0',
            'output_qty' => $validated['output_qty'] ?? 1.00,
            'unit' => $validated['unit'] ?? 'Unit',
            'status' => 'Active',
            'is_active' => true,
            'is_default' => true,
            'routing_name' => 'Standard Assembly Line',
            'created_by' => $request->user()?->name ?? 'Production Manager',
        ]);

        if (! empty($validated['components'])) {
            $totalCost = 0;
            foreach ($validated['components'] as $item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $cost = (float) ($item['unit_cost'] ?? 0);
                $totalCost += ($qty * $cost);

                MrpBomItem::create([
                    'tenant_id' => $tenantId,
                    'bom_id' => $bom->id,
                    'name' => $item['name'] ?? 'Component',
                    'sku' => $item['sku'] ?? 'RM-COMP',
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? 'Unit',
                    'unit_cost' => $cost,
                ]);
            }
            $bom->estimated_cost = $totalCost;
            $bom->unit_cost = $totalCost / max(1, (float) $bom->output_qty);
            $bom->save();
        }

        return redirect()->route('admin.mrp.bom.index')->with('success', "Bill of Materials {$bomNumber} created successfully.");
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $bom = MrpBom::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:50'],
            'version' => ['nullable', 'string'],
            'output_qty' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $bom->update($validated);

        return redirect()->route('admin.mrp.bom.show', $id)->with('success', 'Bill of Materials updated successfully.');
    }

    public function approve(Request $request, int|string $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $bom = MrpBom::where('tenant_id', $tenantId)->findOrFail($id);
        $bom->status = 'Active';
        $bom->is_active = true;
        $bom->save();

        return redirect()->route('admin.mrp.bom.show', $id)->with('success', 'BOM version approved and set to Active.');
    }

    public function duplicate(Request $request, int|string $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $original = MrpBom::where('tenant_id', $tenantId)->with('items')->findOrFail($id);

        $bomCount = MrpBom::where('tenant_id', $tenantId)->count();
        $newBomNumber = 'BOM-'.date('Y').'-'.sprintf('%03d', $bomCount + 1);

        $newBom = $original->replicate();
        $newBom->bom_number = $newBomNumber;
        $newBom->status = 'Draft';
        $newBom->is_default = false;
        $newBom->version = 'v'.(floatval(str_replace('v', '', $original->version)) + 0.1);
        $newBom->save();

        foreach ($original->items as $item) {
            $newItem = $item->replicate();
            $newItem->bom_id = $newBom->id;
            $newItem->save();
        }

        return redirect()->route('admin.mrp.bom.index')->with('success', "BOM duplicated as {$newBomNumber}.");
    }
}
