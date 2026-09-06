<?php

namespace Modules\Tenancy\Application\Actions\Organization;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Application\DTOs\UpdateCompanyProfileData;
use Modules\Tenancy\Models\Tenant;

final class UpdateCompanyProfileAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function execute(UpdateCompanyProfileData $data): Tenant
    {
        return $this->handle($data);
    }

    public function handle(UpdateCompanyProfileData $data): Tenant
    {
        $tenant = $this->currentTenant->get();

        $mergedSettings = array_merge(
            $tenant->settings ?? [],
            $data->settings
        );

        $tenant->update([
            'name' => $data->name,
            'slug' => $data->slug,
            'industry' => $data->industry,
            'country_code' => $data->countryCode,
            'timezone' => $data->timezone,
            'locale' => $data->locale,
            'currency' => strtoupper($data->currency),
            'settings' => $mergedSettings,
        ]);

        return $tenant->refresh();
    }
}
