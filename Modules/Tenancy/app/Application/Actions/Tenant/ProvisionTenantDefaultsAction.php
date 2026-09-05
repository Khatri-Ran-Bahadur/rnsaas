<?php

namespace Modules\Tenancy\Application\Actions\Tenant;

use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;

final class ProvisionTenantDefaultsAction
{
    public function handle(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant): void {
            Department::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => 'GENERAL',
                ],
                [
                    'name' => 'General',
                    'status' => 'active',
                ]
            );

            Department::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => 'ADMIN',
                ],
                [
                    'name' => 'Administration',
                    'status' => 'active',
                ]
            );

            Designation::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => 'STAFF',
                ],
                [
                    'name' => 'Staff',
                    'status' => 'active',
                ]
            );

            Designation::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => 'MANAGER',
                ],
                [
                    'name' => 'Manager',
                    'status' => 'active',
                ]
            );
        });
    }
}
