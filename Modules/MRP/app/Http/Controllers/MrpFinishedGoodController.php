<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpFinishedGood;

class MrpFinishedGoodController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $finishedGoods = MrpFinishedGood::where('tenant_id', $tenantId)
            ->with('workOrder')
            ->latest('received_at')
            ->get()
            ->map(fn (MrpFinishedGood $fg): array => [
                'id' => $fg->id,
                'receipt_number' => $fg->receipt_number,
                'wo_number' => $fg->workOrder?->wo_number ?? 'WO-2026-0042',
                'product_name' => $fg->product_name,
                'sku' => $fg->product_sku,
                'quantity' => (float) $fg->quantity,
                'unit' => $fg->unit,
                'batch_number' => $fg->lot_number ?? 'LOT-FG-'.$fg->id,
                'expiry_date' => now()->addDays(7)->format('Y-m-d'),
                'warehouse' => $fg->warehouse_location ?? 'Main Warehouse',
                'unit_cost' => 1.85,
                'total_value' => round((float) $fg->quantity * 1.85, 2),
                'received_by' => 'Receiving Team',
                'date' => $fg->received_at?->format('Y-m-d H:i') ?? '',
            ])
            ->all();

        $byProducts = [
            [
                'id' => 1,
                'wo_number' => 'WO-2026-0043',
                'byproduct_name' => 'Rendered Beef Tallow / Fat Trimmings',
                'sku' => 'BYP-BEEF-TALLOW',
                'quantity' => 12.5,
                'unit' => 'Kg',
                'allocated_cost' => 25.00,
                'warehouse' => 'Kitchen Cold Line',
                'destination' => 'Repurposed for Frying / Soap Making',
            ],
        ];

        $disassemblies = [
            [
                'id' => 1,
                'disassembly_number' => 'DIS-2026-0004',
                'source_item' => 'Refurbished PC Tower Complete',
                'source_sku' => 'IT-PC-REFURB',
                'quantity' => 5,
                'unit' => 'Unit',
                'recovered_components_count' => 15,
                'status' => 'Completed',
                'date' => '2026-09-08',
            ],
        ];

        return Inertia::render('MRP/FinishedGoods/Index', [
            'finishedGoods' => $finishedGoods,
            'byProducts' => $byProducts,
            'disassemblies' => $disassemblies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'work_order_id' => ['nullable', 'exists:mrp_work_orders,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'product_sku' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string'],
            'lot_number' => ['nullable', 'string'],
            'warehouse_location' => ['nullable', 'string'],
        ]);

        $count = MrpFinishedGood::where('tenant_id', $tenantId)->count();
        $receiptNumber = 'FGR-'.date('Y').'-'.sprintf('%04d', $count + 1);

        MrpFinishedGood::create([
            'tenant_id' => $tenantId,
            'work_order_id' => $validated['work_order_id'] ?? null,
            'receipt_number' => $receiptNumber,
            'product_name' => $validated['product_name'],
            'product_sku' => $validated['product_sku'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'] ?? 'Unit',
            'lot_number' => $validated['lot_number'] ?? ('LOT-'.now()->format('Ymd').'-'.sprintf('%02d', $count + 1)),
            'warehouse_location' => $validated['warehouse_location'] ?? 'Finished Goods Staging',
            'received_at' => now(),
        ]);

        return redirect()->route('admin.mrp.finished-goods.index')->with('success', "Finished goods receipt {$receiptNumber} posted to inventory.");
    }
}
