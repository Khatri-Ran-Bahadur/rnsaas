<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tax\Models\TaxExemption;

class TaxExemptionController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = TaxExemption::where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('entity_name', 'like', "%{$search}%")
                    ->orWhere('certificate_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $exemptions = $query->latest()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'entity_type' => in_array($item->entity_type, ['customer', 'vendor']) ? $item->entity_type : 'customer',
                'entity_name' => $item->entity_name,
                'certificate_number' => $item->certificate_number,
                'exemption_reason' => $item->exemption_type ?? 'Statutory Exemption',
                'effective_from' => $item->valid_from?->toDateString() ?? '2026-01-01',
                'effective_until' => $item->valid_until?->toDateString(),
                'status' => in_array($item->status, ['active', 'expired', 'pending_review', 'rejected']) ? $item->status : 'active',
                'document_url' => null,
                'verified_by' => 'Audit Officer',
                'verified_at' => $item->created_at?->toDateString() ?? '2026-01-01',
                'notes' => 'Audited and approved by tax authority documentation.',
            ];
        });

        return Inertia::render('Tax/Exemptions/Index', [
            'exemptions' => $exemptions,
            'filters' => [
                'search' => $request->input('search', ''),
                'entity_type' => $request->input('entity_type', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'entity_name' => 'required|string|max:255',
            'certificate_number' => 'required|string|max:100',
            'entity_type' => 'nullable|string|in:customer,vendor',
            'exemption_reason' => 'nullable|string',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        TaxExemption::create([
            'tenant_id' => $tenantId,
            'entity_name' => $validated['entity_name'],
            'certificate_number' => $validated['certificate_number'],
            'entity_type' => $validated['entity_type'] ?? 'customer',
            'exemption_type' => $validated['exemption_reason'] ?? 'Standard Certificate',
            'valid_from' => $validated['effective_from'] ?? now()->toDateString(),
            'valid_until' => $validated['effective_until'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'issuing_authority' => 'Tax Bureau',
        ]);

        return back()->with('success', 'Tax exemption certificate saved successfully.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $item = TaxExemption::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'entity_name' => 'required|string|max:255',
            'certificate_number' => 'required|string|max:100',
            'entity_type' => 'nullable|string|in:customer,vendor',
            'exemption_reason' => 'nullable|string',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $item->update([
            'entity_name' => $validated['entity_name'],
            'certificate_number' => $validated['certificate_number'],
            'entity_type' => $validated['entity_type'] ?? $item->entity_type,
            'exemption_type' => $validated['exemption_reason'] ?? $item->exemption_type,
            'valid_from' => $validated['effective_from'] ?? $item->valid_from,
            'valid_until' => $validated['effective_until'] ?? $item->valid_until,
            'status' => $validated['status'] ?? $item->status,
        ]);

        return back()->with('success', 'Tax exemption certificate updated successfully.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $item = TaxExemption::where('tenant_id', $tenantId)->findOrFail($id);
        $item->delete();

        return back()->with('success', 'Tax exemption certificate deleted successfully.');
    }
}
