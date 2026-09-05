<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SuperAdmin\Services\PlatformAnalyticsService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'name' => 'SathiSaaS Admin',
        'email' => 'admin@sathisaas.test',
    ]);

    $permission = Permission::findOrCreate(
        'analytics.view',
        'web',
    );

    $role = Role::findOrCreate(
        'SuperAdmin',
        'web',
    );

    $role->givePermissionTo($permission);

    $this->user->assignRole($role);
});

it('returns platform analytics overview', function (): void {
    $service = app(PlatformAnalyticsService::class);

    $result = $service->overview();

    expect($result)
        ->toHaveKeys([
            'summary',
            'revenue',
            'organizations',
            'subscriptions',
            'subscription_distribution',
            'recent_growth',
        ]);
});

it('allows a super admin to view platform analytics', function (): void {
    $response = $this
        ->actingAs($this->user)
        ->get('/superadmin/analytics');

    $response
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Analytics/Index')
                ->has('analytics')
                ->has('filters'),
        );
});

it('does not allow users without analytics permission', function (): void {
    $user = User::factory()->create([
        'email' => 'user@sathisaas.test',
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/superadmin/analytics');

    $response->assertForbidden();
});
