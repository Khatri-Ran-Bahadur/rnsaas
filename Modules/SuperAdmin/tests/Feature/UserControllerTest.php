<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\SuperAdmin\Database\Seeders\SuperAdminDatabaseSeeder;
use Modules\Tenancy\Models\Tenant;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SuperAdminDatabaseSeeder::class);

    $this->superAdmin = User::factory()->create([
        'name' => 'Super Administrator',
        'email' => 'superadmin@sathisaas.test',
    ]);

    $this->superAdmin->assignRole(
        Role::findByName('SuperAdmin', 'web')
    );
});

it('allows super admins to view users', function (): void {
    User::factory()->count(3)->create();

    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('superadmin.users.index'));

    $response
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('SuperAdmin/Users/Index')
                ->has('users.data', 4)
                ->has('roles')
                ->has('filters')
        );
});

it('filters users by search', function (): void {
    User::factory()->create([
        'name' => 'John Smith',
        'email' => 'john@example.com',
    ]);

    User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('superadmin.users.index', [
            'search' => 'john',
        ]));

    $response
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('SuperAdmin/Users/Index')
                ->where('filters.search', 'john')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'John Smith')
        );
});

it('filters users by role', function (): void {
    $manager = Role::findOrCreate('Manager', 'web');

    $managerUser = User::factory()->create([
        'name' => 'Manager User',
    ]);

    $managerUser->assignRole($manager);

    User::factory()->create([
        'name' => 'Normal User',
    ]);

    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('superadmin.users.index', [
            'role' => 'Manager',
        ]));

    $response
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('SuperAdmin/Users/Index')
                ->where('filters.role', 'Manager')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Manager User')
        );
});

it('includes tenant count for every user', function (): void {
    $user = User::factory()->create();

    Tenant::factory()->count(2)->create()->each(
        function (Tenant $tenant) use ($user): void {
            $tenant->users()->attach($user->id);
        }
    );

    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('superadmin.users.index'));

    $response
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('SuperAdmin/Users/Index')
                ->where(
                    'users.data',
                    fn ($users) => collect($users)
                        ->firstWhere('id', $user->id)['tenants_count'] === 2
                )
        );
});

it('denies users without users view permission', function (): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('superadmin.users.index'));

    $response->assertForbidden();
});
