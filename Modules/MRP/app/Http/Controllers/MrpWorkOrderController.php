<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpBom;
use Modules\MRP\Models\MrpWorkCenter;
use Modules\MRP\Models\MrpWorkOrder;

class MrpWorkOrderController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $workOrders = MrpWorkOrder::where('tenant_id', $tenantId)
            ->with(['bom', 'workCenter'])
            ->latest()
            ->get()
            ->map(fn (MrpWorkOrder $wo): array => [
                'id' => $wo->id,
                'wo_number' => $wo->wo_number,
                'product_name' => $wo->product_name,
                'product_sku' => $wo->product_sku,
                'bom_number' => $wo->bom?->bom_number ?? 'BOM-2026-001',
                'bom_version' => $wo->bom_version,
                'planned_qty' => (float) $wo->planned_qty,
                'produced_qty' => (float) $wo->produced_qty,
                'remaining_qty' => max(0, (float) ($wo->planned_qty - $wo->produced_qty)),
                'unit' => $wo->unit,
                'branch' => $wo->branch ?? 'Main Plant',
                'work_center' => $wo->workCenter?->name ?? 'Default Station',
                'priority' => $wo->priority,
                'status' => $wo->status,
                'progress_percent' => (int) $wo->progress_percent,
                'start_date' => $wo->start_date?->format('Y-m-d H:i') ?? '',
                'due_date' => $wo->due_date?->format('Y-m-d H:i') ?? '',
                'created_at' => $wo->created_at?->format('Y-m-d H:i') ?? '',
            ])
            ->all();

        return Inertia::render('MRP/WorkOrders/Index', [
            'workOrders' => $workOrders,
        ]);
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = $this->getTenantId($request);

        $order = MrpWorkOrder::where('tenant_id', $tenantId)
            ->with(['bom.items', 'workCenter', 'qualityInspections', 'materialIssues', 'finishedGoods'])
            ->findOrFail($id);

        $workOrderData = [
            'id' => $order->id,
            'wo_number' => $order->wo_number,
            'status' => $order->status,
            'priority' => $order->priority,
            'product_name' => $order->product_name,
            'product_sku' => $order->product_sku,
            'product_description' => 'Manufactured under standard MRP work instructions.',
            'bom_number' => $order->bom?->bom_number ?? 'BOM-2026-001',
            'bom_version' => $order->bom_version,
            'planned_qty' => (float) $order->planned_qty,
            'produced_qty' => (float) $order->produced_qty,
            'scrap_qty' => (float) $order->rejected_qty,
            'remaining_qty' => max(0, (float) ($order->planned_qty - $order->produced_qty)),
            'unit' => $order->unit,
            'progress_percent' => (int) $order->progress_percent,
            'branch' => $order->branch ?? 'Central Facility',
            'work_center' => $order->workCenter?->name ?? 'Primary Station',
            'created_by' => 'Production Planner',
            'assigned_to' => $order->assigned_to ?? 'Lead Operator',
            'start_date' => $order->start_date?->format('Y-m-d H:i') ?? '',
            'due_date' => $order->due_date?->format('Y-m-d H:i') ?? '',
            'materials' => $order->bom?->items->map(fn ($item): array => [
                'id' => $item->id,
                'item_name' => $item->name,
                'sku' => $item->sku,
                'required_qty' => (float) $item->quantity * (float) $order->planned_qty,
                'issued_qty' => (float) $item->quantity * (float) $order->produced_qty,
                'unit' => $item->unit,
                'status' => $order->status === 'Completed' ? 'Fully Issued' : 'Partially Issued',
            ])->all() ?? [],
            'operations' => [
                [
                    'id' => 1,
                    'sequence' => 10,
                    'name' => 'Raw Material Staging & Formulation',
                    'work_center' => $order->workCenter?->name ?? 'Prep Station',
                    'planned_time_minutes' => 60,
                    'actual_time_minutes' => 55,
                    'status' => 'Completed',
                ],
                [
                    'id' => 2,
                    'sequence' => 20,
                    'name' => 'Main Manufacturing & Processing Run',
                    'work_center' => $order->workCenter?->name ?? 'Line 1',
                    'planned_time_minutes' => 240,
                    'actual_time_minutes' => 210,
                    'status' => $order->status === 'Completed' ? 'Completed' : 'In Progress',
                ],
            ],
            'quality' => $order->qualityInspections->map(fn ($qi): array => [
                'id' => $qi->id,
                'checkpoint' => $qi->checkpoint_name,
                'inspector' => $qi->inspector ?? 'Inspector',
                'result' => $qi->status,
                'timestamp' => $qi->inspection_date?->format('Y-m-d H:i') ?? '',
                'remarks' => $qi->notes ?? '',
            ])->all(),
            'audit' => [
                ['action' => "Status changed to {$order->status}", 'user' => 'Production System', 'timestamp' => $order->updated_at?->format('Y-m-d H:i') ?? '', 'ip' => '127.0.0.1'],
            ],
        ];

        return Inertia::render('MRP/WorkOrders/Show', [
            'order' => $workOrderData,
            'workOrder' => $workOrderData,
        ]);
    }

    public function create(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $boms = MrpBom::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn (MrpBom $bom): array => [
                'id' => $bom->id,
                'bom_number' => $bom->bom_number,
                'product_name' => $bom->product_name,
                'sku' => $bom->sku,
                'output_qty' => (float) $bom->output_qty,
                'unit' => $bom->unit,
                'estimated_cost' => (float) $bom->estimated_cost,
                'routing_name' => $bom->routing_name ?? 'Default Line',
            ])
            ->all();

        $workCenters = MrpWorkCenter::where('tenant_id', $tenantId)
            ->where('status', 'operational')
            ->get()
            ->map(fn (MrpWorkCenter $wc): array => [
                'id' => $wc->id,
                'name' => $wc->name,
                'code' => $wc->code,
            ])
            ->all();

        return Inertia::render('MRP/WorkOrders/Create', [
            'boms' => $boms,
            'workCenters' => $workCenters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'bom_id' => ['nullable', 'exists:mrp_boms,id'],
            'work_center_id' => ['nullable', 'exists:mrp_work_centers,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'product_sku' => ['required', 'string', 'max:50'],
            'planned_qty' => ['required', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'branch' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'string'],
        ]);

        $count = MrpWorkOrder::where('tenant_id', $tenantId)->count();
        $woNumber = 'WO-'.date('Y').'-'.sprintf('%04d', $count + 1);

        $bom = ! empty($validated['bom_id']) ? MrpBom::find($validated['bom_id']) : null;

        $wo = MrpWorkOrder::create([
            'tenant_id' => $tenantId,
            'wo_number' => $woNumber,
            'bom_id' => $validated['bom_id'] ?? null,
            'work_center_id' => $validated['work_center_id'] ?? null,
            'product_name' => $validated['product_name'],
            'product_sku' => $validated['product_sku'],
            'bom_version' => $bom?->version ?? 'v1.0',
            'planned_qty' => $validated['planned_qty'],
            'produced_qty' => 0.00,
            'rejected_qty' => 0.00,
            'unit' => $validated['unit'] ?? ($bom?->unit ?? 'Unit'),
            'branch' => $validated['branch'] ?? 'Central Production Hub',
            'priority' => $validated['priority'] ?? 'Normal',
            'status' => 'Confirmed',
            'progress_percent' => 0,
            'start_date' => now(),
            'due_date' => ! empty($validated['due_date']) ? $validated['due_date'] : now()->addDays(3),
            'assigned_to' => $validated['assigned_to'] ?? ($request->user()?->name ?? 'Operator'),
        ]);

        return redirect()->route('admin.mrp.work-orders.index')->with('success', "Work Order {$woNumber} created successfully.");
    }

    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $order = MrpWorkOrder::where('tenant_id', $tenantId)->findOrFail($id);

        $status = $request->input('status', 'In Progress');
        $order->status = $status;

        if ($status === 'Completed') {
            $order->progress_percent = 100;
            $order->produced_qty = $order->planned_qty;
            $order->completed_at = now();
        } elseif ($status === 'In Progress' && $order->progress_percent === 0) {
            $order->progress_percent = 25;
        }

        $order->save();

        return redirect()->route('admin.mrp.work-orders.show', $id)->with('success', "Work Order status updated to {$status}.");
    }
}
