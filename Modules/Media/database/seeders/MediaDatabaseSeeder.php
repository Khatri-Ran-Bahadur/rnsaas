<?php

namespace Modules\Media\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MediaDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'media.view',
            'media.create',
            'media.download',
            'media.delete',
            'media.directories.create',
            'media.directories.update',
            'media.directories.delete',
            'media.manage-any',
            'media.manage-own',
        ];

        $permissionModels = collect($permissions)->map(
            fn (string $permission): Permission => Permission::findOrCreate($permission, 'web')
        );

        $superAdmin = Role::findOrCreate('SuperAdmin', 'web');
        $superAdmin->givePermissionTo($permissionModels);
    }
}
