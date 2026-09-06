<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admin\Http\Requests\UpdateCompanyProfileRequest;
use Modules\Tenancy\Application\Actions\Organization\UpdateCompanyProfileAction;

class CompanyProfileController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function edit(): Response
    {
        $this->authorize('company_profile.view');

        $tenant = $this->currentTenant->get();
        $tenant->loadCount(['branches', 'departments', 'designations', 'staff', 'users']);

        return Inertia::render('Admin/CompanyProfile/Edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(
        UpdateCompanyProfileRequest $request,
        UpdateCompanyProfileAction $action,
    ): RedirectResponse {
        $this->authorize('company_profile.update');

        $tenant = $action->execute($request->toData());

        return redirect()->route('admin.company-profile.edit')
            ->with('success', "Company profile for '{$tenant->name}' updated successfully.");
    }
}
