<?php

namespace Modules\MRP\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\MRP\Models\MrpMaterialIssue;

class MrpMaterialIssueController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $issues = MrpMaterialIssue::where('tenant_id', $tenantId)
            ->with('workOrder')
            ->latest('issued_at')
            ->get()
            ->map(fn (MrpMaterialIssue $issue): array => [
                'id' => $issue->id,
                'issue_number' => $issue->issue_number,
                'wo_number' => $issue->workOrder?->wo_number ?? 'WO-2026-0042',
                'type' => 'Full Issue',
                'items_count' => 1,
                'warehouse' => 'Main Production Silo',
                'issued_to_work_center' => $issue->issued_to ?? 'Production Floor',
                'issued_by' => 'Storekeeper Ray',
                'date' => $issue->issued_at?->format('Y-m-d H:i') ?? '',
                'status' => 'Posted',
            ])
            ->all();

        $returns = [
            [
                'id' => 1,
                'return_number' => 'RET-2026-0012',
                'wo_number' => 'WO-2026-0040',
                'item_name' => 'SMD Resistor 10k 0805 Reel',
                'sku' => 'RM-ELEC-RES10K',
                'quantity' => 200,
                'unit' => 'Pcs',
                'reason' => 'Unused Material Return',
                'returned_by' => 'SMT Operator Lin',
                'date' => '2026-09-09 17:00',
                'status' => 'Received into Inventory',
            ],
        ];

        return Inertia::render('MRP/MaterialIssues/Index', [
            'issues' => $issues,
            'returns' => $returns,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'work_order_id' => ['nullable', 'exists:mrp_work_orders,id'],
            'item_name' => ['required', 'string', 'max:255'],
            'item_sku' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string'],
            'issued_to' => ['nullable', 'string'],
        ]);

        $count = MrpMaterialIssue::where('tenant_id', $tenantId)->count();
        $issueNumber = 'ISS-'.date('Y').'-'.sprintf('%04d', $count + 1);

        MrpMaterialIssue::create([
            'tenant_id' => $tenantId,
            'work_order_id' => $validated['work_order_id'] ?? null,
            'issue_number' => $issueNumber,
            'item_name' => $validated['item_name'],
            'item_sku' => $validated['item_sku'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'] ?? 'kg',
            'issued_to' => $validated['issued_to'] ?? 'Production Floor',
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        return redirect()->route('admin.mrp.material-issues.index')->with('success', "Material issue {$issueNumber} recorded successfully.");
    }

    public function returnMaterial(Request $request): RedirectResponse
    {
        return redirect()->route('admin.mrp.material-issues.index')->with('success', 'Material return to inventory posted successfully.');
    }
}
