<?php

namespace Modules\SuperAdmin\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard.view',

            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',

            'members.view',
            'members.invite',
            'members.update',
            'members.revoke',

            'audit.view',

            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.update',
            'subscriptions.cancel',

            'payments.view',
            'payments.refund',
            'users.view',

            'media.view',
            'media.create',
            'media.download',
            'media.delete',
            'media.directories.create',
            'media.directories.update',
            'media.directories.delete',
            'media.manage-any',
            'media.manage-own',

            'settings.view',
            'settings.update',
            'settings.cache.clear',

            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            'security.view',
            'security.sessions.revoke',
            'analytics.view',
        ];

        $permissionModels = collect($permissions)
            ->map(
                fn (string $permission): Permission => Permission::findOrCreate(
                    $permission,
                    'web',
                )
            );

        $superAdmin = Role::findOrCreate('SuperAdmin', 'web');

        $superAdmin->syncPermissions($permissionModels);
    }
}
