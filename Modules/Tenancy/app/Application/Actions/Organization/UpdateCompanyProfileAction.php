<?php

namespace Modules\Tenancy\Application\Actions\Organization;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tax\Models\TaxSetting;
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

        if (class_exists(TaxSetting::class)) {
            $taxSetting = TaxSetting::firstOrNew(['tenant_id' => $tenant->id]);
            $taxSetting->country = $data->countryCode ?: $taxSetting->country ?: 'Global';
            $taxSetting->registered_business_name = $data->name;
            if (! empty($mergedSettings['tax_id'])) {
                $taxSetting->tax_registration_number = $mergedSettings['tax_id'];
            }
            if (strtoupper((string) $data->countryCode) === 'NP' && (empty($taxSetting->tax_regime) || $taxSetting->tax_regime === 'sst')) {
                $taxSetting->tax_regime = 'vat';
                $taxSetting->tax_authority_name = 'Inland Revenue Department (IRD / आन्तरिक राजस्व विभाग)';
            }
            $taxSetting->save();
        }

        return $tenant->refresh();
    }
}
