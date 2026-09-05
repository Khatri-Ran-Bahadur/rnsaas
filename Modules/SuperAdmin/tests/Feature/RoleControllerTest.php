<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SuperAdmin\Database\Seeders\SuperAdminDatabaseSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SuperAdminDatabaseSeeder::class);

    $this->superAdmin = User::factory()->create();

    $this->superAdmin->assignRole(
        Role::findByName('SuperAdmin', 'web'),
    );
});

it('allows a superadmin to view platform roles', function (): void {
    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('superadmin.roles.index'));

    $response
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Roles/Index')
                ->has('roles')
                ->has('permission_groups'),
        );
});

it('allows a superadmin to view the create role page', function (): void {
    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('superadmin.roles.create'));

    $response
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Roles/Create')
                ->has('permission_groups'),
        );
});

it('allows a superadmin to view the edit role page for a custom role', function (): void {
    $role = Role::create([
        'name' => 'Support Agent',
        'guard_name' => 'web',
    ]);

    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('superadmin.roles.edit', $role));

    $response
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Roles/Edit')
                ->has('role')
                ->has('permission_groups')
                ->where('role.name', 'Support Agent'),
        );
});

it('prevents viewing the edit page for SuperAdmin role', function (): void {
    $role = Role::findByName('SuperAdmin', 'web');

    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('superadmin.roles.edit', $role));

    $response
        ->assertRedirect(route('superadmin.roles.index'))
        ->assertSessionHas('error');
});

it('creates a platform role with selected permissions', function (): void {
    $permission = Permission::findByName(
        'users.view',
        'web',
    );

    $response = $this
        ->actingAs($this->superAdmin)
        ->post(route('superadmin.roles.store'), [
            'name' => 'Support Agent',
            'permissions' => [
                $permission->name,
            ],
        ]);

    $response
        ->assertRedirect()
        ->assertSessionHas(
            'success',
            'Platform role created successfully.',
        );

    $role = Role::findByName(
        'Support Agent',
        'web',
    );

    expect($role)->not->toBeNull();

    expect($role->hasPermissionTo(
        $permission,
    ))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'platform.role.created',
    ]);
});

it('updates a platform role and synchronizes permissions', function (): void {
    $firstPermission = Permission::findByName(
        'users.view',
        'web',
    );

    $secondPermission = Permission::findByName(
        'tenants.view',
        'web',
    );

    $role = Role::create([
        'name' => 'Support Agent',
        'guard_name' => 'web',
    ]);

    $role->givePermissionTo($firstPermission);

    $response = $this
        ->actingAs($this->superAdmin)
        ->put(
            route('superadmin.roles.update', $role),
            [
                'name' => 'Support Manager',
                'permissions' => [
                    $secondPermission->name,
                ],
            ],
        );

    $response
        ->assertRedirect()
        ->assertSessionHas(
            'success',
            'Platform role updated successfully.',
        );

    $role->refresh();

    expect($role->name)->toBe('Support Manager');
    expect($role->hasPermissionTo(
        $secondPermission,
    ))->toBeTrue();
    expect($role->hasPermissionTo(
        $firstPermission,
    ))->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'platform.role.updated',
    ]);
});

it('prevents modifying the SuperAdmin role', function (): void {
    $role = Role::findByName(
        'SuperAdmin',
        'web',
    );

    $permission = Permission::findByName(
        'users.view',
        'web',
    );

    $response = $this
        ->actingAs($this->superAdmin)
        ->put(
            route('superadmin.roles.update', $role),
            [
                'name' => 'Platform Administrator',
                'permissions' => [
                    $permission->name,
                ],
            ],
        );

    $response->assertStatus(422);

    expect($role->fresh()->name)->toBe('SuperAdmin');
});

it('prevents deleting the SuperAdmin role', function (): void {
    $role = Role::findByName(
        'SuperAdmin',
        'web',
    );

    $response = $this
        ->actingAs($this->superAdmin)
        ->delete(
            route('superadmin.roles.destroy', $role),
        );

    $response->assertStatus(422);

    expect(
        Role::findByName('SuperAdmin', 'web'),
    )->not->toBeNull();
});

it('prevents deleting a role assigned to users', function (): void {
    $role = Role::create([
        'name' => 'Support Agent',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();

    $user->assignRole($role);

    $response = $this
        ->actingAs($this->superAdmin)
        ->delete(
            route('superadmin.roles.destroy', $role),
        );

    $response->assertStatus(422);

    expect(
        Role::findByName('Support Agent', 'web'),
    )->not->toBeNull();
});

it('deletes an unused platform role', function (): void {
    $role = Role::create([
        'name' => 'Temporary Role',
        'guard_name' => 'web',
    ]);

    $response = $this
        ->actingAs($this->superAdmin)
        ->delete(
            route('superadmin.roles.destroy', $role),
        );

    $response
        ->assertRedirect()
        ->assertSessionHas(
            'success',
            'Platform role deleted successfully.',
        );

    expect(
        Role::where('id', $role->id)->exists(),
    )->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'platform.role.deleted',
    ]);
});

it('denies role management without the required permission', function (): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('superadmin.roles.index'));

    $response->assertForbidden();
});

it('validates duplicate platform role names', function (): void {
    Role::create([
        'name' => 'Support Agent',
        'guard_name' => 'web',
    ]);

    $response = $this
        ->actingAs($this->superAdmin)
        ->post(route('superadmin.roles.store'), [
            'name' => 'Support Agent',
            'permissions' => [],
        ]);

    $response
        ->assertRedirect()
        ->assertSessionHasErrors('name');
});
