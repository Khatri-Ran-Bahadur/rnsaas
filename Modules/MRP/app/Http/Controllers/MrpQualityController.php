<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpQualityInspection;

class MrpQualityController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $allInspections = MrpQualityInspection::where('tenant_id', $tenantId)
            ->latest('inspection_date')
            ->get();

        $totalCount = $allInspections->count();
        $passedCount = $allInspections->where('status', 'passed')->count();
        $failedCount = $allInspections->where('status', 'failed')->count();

        $inspections = $allInspections->map(fn (MrpQualityInspection $qi): array => [
            'id' => $qi->id,
            'inspection_number' => $qi->inspection_number,
            'type' => $qi->type,
            'wo_number' => $qi->wo_number,
            'production_order' => $qi->wo_number,
            'product_name' => $qi->product_name,
            'item_name' => $qi->product_name,
            'item_code' => $qi->item_code ?? 'SKU-'.$qi->id,
            'lot_or_batch' => $qi->lot_or_batch ?? 'LOT-BATCH-'.$qi->id,
            'checkpoint_name' => $qi->checkpoint_name,
            'target' => $qi->target ?? 'Standard Compliance',
            'actual' => $qi->actual ?? 'Compliant',
            'sample_size' => (int) $qi->sample_size,
            'inspected_qty' => (int) $qi->inspected_qty,
            'passed_qty' => (int) $qi->passed_qty,
            'failed_qty' => (int) $qi->failed_qty,
            'rejected_qty' => (int) $qi->failed_qty,
            'status' => $qi->status,
            'ncr_number' => $qi->status === 'failed' ? 'NCR-'.date('Y').'-'.sprintf('%03d', $qi->id) : null,
            'inspector' => $qi->inspector ?? 'QA Officer',
            'inspection_date' => $qi->inspection_date?->format('Y-m-d H:i') ?? '',
            'notes' => $qi->notes ?? '',
        ])->all();

        $summary = [
            'total_inspections' => $totalCount,
            'pass_rate_percentage' => $totalCount > 0 ? round(($passedCount / $totalCount) * 100, 1) : 100.0,
            'open_ncrs' => $failedCount,
            'quarantined_batches' => $failedCount,
        ];

        return Inertia::render('MRP/Quality/Index', [
            'inspections' => $inspections,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'work_order_id' => ['nullable', 'exists:mrp_work_orders,id'],
            'type' => ['nullable', 'string'],
            'wo_number' => ['nullable', 'string'],
            'product_name' => ['required', 'string', 'max:255'],
            'checkpoint_name' => ['required', 'string', 'max:255'],
            'target' => ['nullable', 'string'],
            'actual' => ['nullable', 'string'],
            'sample_size' => ['nullable', 'integer'],
            'passed_qty' => ['nullable', 'integer'],
            'failed_qty' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:passed,failed,pending'],
            'notes' => ['nullable', 'string'],
        ]);

        $count = MrpQualityInspection::where('tenant_id', $tenantId)->count();
        $inspectionNumber = 'QC-'.date('Y').'-'.sprintf('%04d', $count + 1);

        $sampleSize = $validated['sample_size'] ?? 5;
        $failedQty = $validated['failed_qty'] ?? 0;
        $passedQty = $validated['passed_qty'] ?? ($sampleSize - $failedQty);

        MrpQualityInspection::create([
            'tenant_id' => $tenantId,
            'work_order_id' => $validated['work_order_id'] ?? null,
            'inspection_number' => $inspectionNumber,
            'type' => $validated['type'] ?? 'in_process',
            'wo_number' => $validated['wo_number'] ?? null,
            'product_name' => $validated['product_name'],
            'checkpoint_name' => $validated['checkpoint_name'],
            'target' => $validated['target'] ?? null,
            'actual' => $validated['actual'] ?? null,
            'sample_size' => $sampleSize,
            'inspected_qty' => $sampleSize,
            'passed_qty' => $passedQty,
            'failed_qty' => $failedQty,
            'status' => $validated['status'] ?? ($failedQty > 0 ? 'failed' : 'passed'),
            'inspector' => $request->user()?->name ?? 'QA Inspector',
            'inspection_date' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.mrp.quality.index')->with('success', "Quality inspection {$inspectionNumber} recorded successfully.");
    }
}
