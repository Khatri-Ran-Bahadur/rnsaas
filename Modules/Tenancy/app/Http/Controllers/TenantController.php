<?php

namespace Modules\Tenancy\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Http\Requests\StoreTenantRequest;
use Modules\Tenancy\Models\Tenant;

class TenantController
{
    public function index(Request $request): Response
    {
        $query = Tenant::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('industry', 'like', "%{$search}%")
                    ->orWhere('country_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($industry = $request->input('industry')) {
            $query->where('industry', $industry);
        }

        $perPage = $request->integer('per_page') ?: 10;
        $perPage = min(max($perPage, 5), 100);

        $tenants = $query->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Tenancy/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'status' => (string) $request->input('status', ''),
                'industry' => (string) $request->input('industry', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tenancy/Tenants/Create');
    }

    public function store(
        StoreTenantRequest $request,
        CreateTenantAction $action,
    ): RedirectResponse {
        $action->execute($request->toData());

        return to_route('tenancy.index')
            ->with('success', 'Organization created successfully.');
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->load(['users']);

        return Inertia::render('Tenancy/Tenants/Show', [
            'tenant' => $tenant,
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('Tenancy/Tenants/Edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:150', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'industry' => ['nullable', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $tenant->update($validated);

        return to_route('tenancy.index')
            ->with('success', 'Organization updated successfully.');
    }
}
