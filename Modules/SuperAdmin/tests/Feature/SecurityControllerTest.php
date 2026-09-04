<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Audit\Models\AuditLog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'name' => 'SathiSaaS Admin',
        'email' => 'admin@sathisaas.test',
    ]);

    $permission = Permission::findOrCreate(
        'security.view',
        'web',
    );

    $role = Role::findOrCreate(
        'SuperAdmin',
        'web',
    );

    $role->givePermissionTo($permission);

    $this->user->assignRole($role);
});

it('allows a super admin to view the security center', function (): void {
    $response = $this
        ->actingAs($this->user)
        ->get('/admin/security');

    $response
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('Security/Index')
                ->has('overview')
                ->has('loginActivity')
                ->has('recentEvents')
                ->has('activeSessions')
                ->has('authenticationEvents')
                ->has('filters'),
        );
});

it('returns authentication events in the security center', function (): void {
    AuditLog::create([
        'event' => 'auth.login.success',
        'actor_type' => User::class,
        'actor_id' => $this->user->id,
        'auditable_type' => User::class,
        'auditable_id' => $this->user->id,
        'metadata' => [
            'guard' => 'web',
            'remember' => false,
        ],
        'created_at' => now(),
    ]);

    AuditLog::create([
        'event' => 'auth.login.failed',
        'metadata' => [
            'identifier' => 'unknown@example.com',
            'user_found' => false,
        ],
        'created_at' => now(),
    ]);

    $response = $this
        ->actingAs($this->user)
        ->get('/admin/security');

    $response
        ->assertSuccessful()
        ->assertInertia(
            fn (Assert $page) => $page
                ->where(
                    'overview.successful_logins_today',
                    1,
                )
                ->where(
                    'overview.failed_logins_today',
                    1,
                )
                ->has('loginActivity.data', 2),
        );
});

it('does not expose password data through the security center', function (): void {
    AuditLog::create([
        'event' => 'auth.login.failed',
        'metadata' => [
            'identifier' => 'unknown@example.com',
        ],
        'created_at' => now(),
    ]);

    $response = $this
        ->actingAs($this->user)
        ->get('/admin/security');

    $response
        ->assertSuccessful()
        ->assertDontSee('super-secret-password');
});