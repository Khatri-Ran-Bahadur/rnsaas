<?php

namespace Modules\Tenancy\Application\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Models\Tenant;

final class CreateTenantAction
{
    public function execute(CreateTenantData $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            return Tenant::create([
                'public_id' => (string) Str::ulid(),
                'name' => $data->name,
                'slug' => $data->slug,
                'industry' => $data->industry,
                'status' => $data->status,
                'country_code' => $data->countryCode,
                'timezone' => $data->timezone,
                'locale' => $data->locale,
                'currency' => $data->currency,
                'settings' => $data->settings,
            ]);
        });
    }
}
