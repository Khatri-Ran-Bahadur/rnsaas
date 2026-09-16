<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Models\TaxType;
use Modules\Tenancy\Models\Tenant;

class TaxRateController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = TaxRate::where('tenant_id', $tenantId)->with('taxType:id,name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tax_type_id')) {
            $query->where('tax_type_id', $request->input('tax_type_id'));
        }

        if ($request->filled('timeline_status')) {
            $query->where('timeline_status', $request->input('timeline_status'));
        }

        if ($request->filled('tax_category')) {
            $query->where('tax_category', $request->input('tax_category'));
        }

        $tenant = Tenant::find($tenantId);
        $defaultCountry = $tenant?->country_code ?: 'NP';

        $rates = $query->orderBy('name')->get()->map(function ($rate) use ($defaultCountry) {
            return [
                'id' => $rate->id,
                'name' => $rate->name,
                'code' => $rate->code,
                'tax_type_id' => $rate->tax_type_id,
                'tax_type_name' => $rate->taxType?->name ?? 'Standard',
                'tax_category' => $rate->tax_category ?? 'standard',
                'rate_type' => $rate->rate_type ?? 'percentage',
                'rate' => (float) $rate->rate,
                'fixed_amount' => $rate->fixed_amount ? (float) $rate->fixed_amount : null,
                'effective_from' => $rate->effective_from?->toDateString() ?? $rate->created_at?->toDateString() ?? now()->toDateString(),
                'effective_until' => $rate->effective_until?->toDateString(),
                'timeline_status' => $rate->timeline_status ?? 'active',
                'country' => $rate->country ?? $defaultCountry,
                'region' => $rate->region ?? 'National',
                'is_recoverable' => (bool) $rate->is_recoverable,
                'is_compound' => (bool) $rate->is_compound,
                'description' => $rate->description,
            ];
        });

        $taxTypes = TaxType::where('tenant_id', $tenantId)->get(['id', 'name'])->toArray();

        return Inertia::render('Tax/Rates/Index', [
            'taxRates' => $rates,
            'taxTypes' => $taxTypes,
            'filters' => [
                'search' => $request->input('search', ''),
                'tax_type_id' => $request->input('tax_type_id', ''),
                'timeline_status' => $request->input('timeline_status', ''),
                'tax_category' => $request->input('tax_category', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'tax_type_id' => 'required|integer',
            'tax_category' => 'nullable|string',
            'rate_type' => 'nullable|string|in:percentage,fixed',
            'rate' => 'nullable|numeric|min:0',
            'fixed_amount' => 'nullable|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date',
            'timeline_status' => 'nullable|string|in:active,scheduled,expired,draft',
            'country' => 'nullable|string',
            'region' => 'nullable|string',
            'is_recoverable' => 'nullable|boolean',
            'is_compound' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        TaxRate::create([
            'tenant_id' => $tenantId,
            'tax_type_id' => $validated['tax_type_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'tax_category' => $validated['tax_category'] ?? 'standard',
            'rate_type' => $validated['rate_type'] ?? 'percentage',
            'rate' => $validated['rate'] ?? 0.00,
            'fixed_amount' => $validated['fixed_amount'] ?? null,
            'effective_from' => $validated['effective_from'] ?? now()->toDateString(),
            'effective_until' => $validated['effective_until'] ?? null,
            'timeline_status' => $validated['timeline_status'] ?? 'active',
            'country' => $validated['country'] ?? 'Global',
            'region' => $validated['region'] ?? 'All Regions',
            'is_recoverable' => $validated['is_recoverable'] ?? true,
            'is_compound' => $validated['is_compound'] ?? false,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Tax rate created successfully.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $rate = TaxRate::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'tax_type_id' => 'required|integer',
            'tax_category' => 'nullable|string',
            'rate_type' => 'nullable|string|in:percentage,fixed',
            'rate' => 'nullable|numeric|min:0',
            'fixed_amount' => 'nullable|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date',
            'timeline_status' => 'nullable|string|in:active,scheduled,expired,draft',
            'country' => 'nullable|string',
            'region' => 'nullable|string',
            'is_recoverable' => 'nullable|boolean',
            'is_compound' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $rate->update($validated);

        return back()->with('success', 'Tax rate updated successfully.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $rate = TaxRate::where('tenant_id', $tenantId)->findOrFail($id);
        $rate->delete();

        return back()->with('success', 'Tax rate deleted successfully.');
    }
}
