<?php

namespace Modules\Tenancy\Application\Actions\Tenant;

use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Domain\Constants\OrganizationPermissions;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\Designation;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;

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

            // Provision Default Roles
            /** @var TenantRole $adminRole */
            $adminRole = TenantRole::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => 'admin',
                ],
                [
                    'name' => 'Admin',
                    'description' => 'Full administrative access to organization resources.',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
            if (! $adminRole->permissions()->exists()) {
                $adminRole->syncPermissions(OrganizationPermissions::adminDefault());
            }

            /** @var TenantRole $managerRole */
            $managerRole = TenantRole::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => 'manager',
                ],
                [
                    'name' => 'Manager',
                    'description' => 'Manage departments, staff, attendance, and leave requests.',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
            if (! $managerRole->permissions()->exists()) {
                $managerRole->syncPermissions(OrganizationPermissions::managerDefault());
            }

            /** @var TenantRole $staffRole */
            $staffRole = TenantRole::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => 'staff',
                ],
                [
                    'name' => 'Staff',
                    'description' => 'Standard staff access to view directory, submit attendance, and request leaves.',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
            if (! $staffRole->permissions()->exists()) {
                $staffRole->syncPermissions(OrganizationPermissions::staffDefault());
            }

            // Assign Admin role to existing memberships lacking a role
            TenantMembership::query()
                ->where('tenant_id', $tenant->id)
                ->whereNull('role_id')
                ->update(['role_id' => $adminRole->id]);
        });
    }
}
