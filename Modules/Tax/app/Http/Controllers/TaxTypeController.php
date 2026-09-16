<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tax\Models\TaxSetting;
use Modules\Tax\Models\TaxType;
use Modules\Tenancy\Models\Tenant;

class TaxTypeController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);

        $query = TaxType::where('tenant_id', $tenantId)->withCount('rates');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('scope')) {
            $query->where('scope', $request->input('scope'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tenant = Tenant::find($tenantId);
        $settings = TaxSetting::where('tenant_id', $tenantId)->first();
        $countryCode = strtoupper((string) ($tenant?->country_code ?: $settings?->country ?: 'NP'));
        $authority = $settings?->tax_authority_name ?: 'National Tax Authority';

        $types = $query->orderBy('name')->get()->map(function ($type) use ($countryCode, $authority) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'code' => $type->code,
                'scope' => in_array($type->scope, ['sales', 'purchases', 'both']) ? $type->scope : 'both',
                'country_code' => $countryCode,
                'tax_authority' => $authority,
                'description' => $type->description,
                'rates_count' => (int) $type->rates_count,
                'status' => $type->status === 'inactive' ? 'inactive' : 'active',
                'created_at' => $type->created_at?->toDateString() ?? now()->toDateString(),
            ];
        });

        return Inertia::render('Tax/Types/Index', [
            'taxTypes' => $types,
            'filters' => [
                'search' => $request->input('search', ''),
                'scope' => $request->input('scope', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'scope' => 'nullable|string|in:sales,purchases,both',
            'tax_authority' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        TaxType::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'scope' => $validated['scope'] ?? 'both',
            'status' => $validated['status'] ?? 'active',
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Tax type created successfully.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $type = TaxType::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'scope' => 'nullable|string|in:sales,purchases,both',
            'tax_authority' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $type->update($validated);

        return back()->with('success', 'Tax type updated successfully.');
    }

    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $type = TaxType::where('tenant_id', $tenantId)->findOrFail($id);
        $type->delete();

        return back()->with('success', 'Tax type deleted successfully.');
    }
}
