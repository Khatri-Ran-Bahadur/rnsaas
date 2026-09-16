<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxRule;

class TaxRuleController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = TaxRule::where('tenant_id', $tenantId)->with('taxRate:id,name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->input('transaction_type'));
        }

        if ($request->filled('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $rules = $query->orderBy('priority')->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'priority' => (int) $r->priority,
                'transaction_type' => $r->transaction_type ?? 'sales',
                'sales_channel' => $r->sales_channel ?? 'all',
                'customer_type' => $r->customer_type ?? 'b2c_individual',
                'item_type' => $r->item_type ?? 'all',
                'applied_tax_rate_id' => $r->applied_tax_rate_id,
                'applied_tax_rate_name' => $r->taxRate?->name ?? 'Standard Rate (8%)',
                'tax_inclusive_mode' => (bool) $r->tax_inclusive_mode,
                'is_active' => (bool) $r->is_active,
                'description' => $r->description,
            ];
        });

        $taxRates = TaxRate::where('tenant_id', $tenantId)->get(['id', 'name'])->toArray();

        return Inertia::render('Tax/Rules/Index', [
            'rules' => $rules,
            'taxRates' => $taxRates,
            'filters' => [
                'search' => $request->input('search', ''),
                'transaction_type' => $request->input('transaction_type', ''),
                'is_active' => $request->input('is_active', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'priority' => 'nullable|integer',
            'transaction_type' => 'nullable|string|in:sales,purchases,both',
            'sales_channel' => 'nullable|string',
            'customer_type' => 'nullable|string',
            'item_type' => 'nullable|string',
            'applied_tax_rate_id' => 'required|integer',
            'tax_inclusive_mode' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        TaxRule::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'priority' => $validated['priority'] ?? 10,
            'transaction_type' => $validated['transaction_type'] ?? 'sales',
            'sales_channel' => $validated['sales_channel'] ?? 'all',
            'customer_type' => $validated['customer_type'] ?? 'all',
            'item_type' => $validated['item_type'] ?? 'all',
            'applied_tax_rate_id' => $validated['applied_tax_rate_id'],
            'tax_inclusive_mode' => $validated['tax_inclusive_mode'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Tax rule created successfully.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $rule = TaxRule::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'priority' => 'nullable|integer',
            'transaction_type' => 'nullable|string|in:sales,purchases,both',
            'sales_channel' => 'nullable|string',
            'customer_type' => 'nullable|string',
            'item_type' => 'nullable|string',
            'applied_tax_rate_id' => 'required|integer',
            'tax_inclusive_mode' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $rule->update($validated);

        return back()->with('success', 'Tax rule updated successfully.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $rule = TaxRule::where('tenant_id', $tenantId)->findOrFail($id);
        $rule->delete();

        return back()->with('success', 'Tax rule deleted successfully.');
    }
}
