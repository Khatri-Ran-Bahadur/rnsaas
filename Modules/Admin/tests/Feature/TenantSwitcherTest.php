<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Actions\SwitchCurrentTenantAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

it('allows a user to switch to an active organization', function () {
    $user = User::factory()->create();

    $tenant = Tenant::factory()->create();

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $result = app(SwitchCurrentTenantAction::class)
        ->handle($user, $tenant);

    expect($result->is($tenant))->toBeTrue();
});

it('rejects switching to an organization the user does not belong to', function () {
    $user = User::factory()->create();

    $tenant = Tenant::factory()->create();

    expect(fn () => app(SwitchCurrentTenantAction::class)
        ->handle($user, $tenant))
        ->toThrow(LogicException::class);
});

it('rejects switching to a suspended organization membership', function () {
    $user = User::factory()->create();

    $tenant = Tenant::factory()->create();

    TenantMembership::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::Suspended,
    ]);

    expect(fn () => app(SwitchCurrentTenantAction::class)
        ->handle($user, $tenant))
        ->toThrow(LogicException::class);
});
