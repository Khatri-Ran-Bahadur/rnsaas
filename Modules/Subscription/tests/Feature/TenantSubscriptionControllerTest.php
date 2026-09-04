<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Subscription\Enums\BillingCycle;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function actingAsSubscriptionSuperAdmin(): User
{
    Role::firstOrCreate([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole('SuperAdmin');

    test()->actingAs($user);

    return $user;
}

it('allows a superadmin to view subscription listing', function () {
    actingAsSubscriptionSuperAdmin();

    TenantSubscription::factory()->create();

    $response = $this->get(
        route('admin.subscriptions.index')
    );

    $response->assertOk();
});

it('allows a superadmin to view subscription creation page', function () {
    actingAsSubscriptionSuperAdmin();

    $response = $this->get(
        route('admin.subscriptions.create')
    );

    $response->assertOk();
});

it('allows a superadmin to view a subscription', function () {
    actingAsSubscriptionSuperAdmin();

    $subscription = TenantSubscription::factory()->create();

    $response = $this->get(
        route('admin.subscriptions.show', $subscription)
    );

    $response->assertOk();
});

it('requires superadmin access for subscription listing', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    $response = $this->get(
        route('admin.subscriptions.index')
    );

    $response->assertForbidden();
});

it('paginates subscription listing', function () {
    actingAsSubscriptionSuperAdmin();

    $plan = Plan::factory()->create();

    TenantSubscription::factory()
        ->count(25)
        ->create([
            'plan_id' => $plan->id,
        ]);

    $response = $this->get(
        route('admin.subscriptions.index')
    );

    $response->assertOk();
});

it('respects per_page query parameter for subscription listing', function () {
    actingAsSubscriptionSuperAdmin();

    $plan = Plan::factory()->create();

    TenantSubscription::factory()
        ->count(25)
        ->create([
            'plan_id' => $plan->id,
        ]);

    $response = $this->get(
        route('admin.subscriptions.index', ['per_page' => 10])
    );

    $response->assertOk();
    $page = $response->viewData('page');
    expect($page['props']['subscriptions']['per_page'])->toBe(10)
        ->and($page['props']['subscriptions']['data'])->toHaveCount(10)
        ->and($page['props']['filters']['per_page'])->toBe(10);
});

it('eager loads tenant and plan for subscription listing', function () {
    actingAsSubscriptionSuperAdmin();

    $tenant = Tenant::factory()->create();
    $plan = Plan::factory()->create();

    TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->get(
        route('admin.subscriptions.index')
    );

    $response->assertOk();
});

it('filters subscriptions by status', function () {
    actingAsSubscriptionSuperAdmin();

    TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Pending,
    ]);

    TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->get(
        route('admin.subscriptions.index', ['status' => SubscriptionStatus::Pending->value])
    );

    $response->assertOk();
});

it('filters subscriptions by plan', function () {
    actingAsSubscriptionSuperAdmin();

    $planA = Plan::factory()->create();
    $planB = Plan::factory()->create();

    TenantSubscription::factory()->create(['plan_id' => $planA->id]);
    TenantSubscription::factory()->create(['plan_id' => $planB->id]);

    $response = $this->get(
        route('admin.subscriptions.index', ['plan' => $planA->id])
    );

    $response->assertOk();
});

it('filters subscriptions by billing cycle', function () {
    actingAsSubscriptionSuperAdmin();

    $monthlyPlan = Plan::factory()->create(['billing_cycle' => BillingCycle::Monthly]);
    $yearlyPlan = Plan::factory()->create(['billing_cycle' => BillingCycle::Yearly]);

    TenantSubscription::factory()->create(['plan_id' => $monthlyPlan->id]);
    TenantSubscription::factory()->create(['plan_id' => $yearlyPlan->id]);

    $response = $this->get(
        route('admin.subscriptions.index', ['billing_cycle' => BillingCycle::Monthly->value])
    );

    $response->assertOk();
});

it('searches subscriptions by tenant name', function () {
    actingAsSubscriptionSuperAdmin();

    $tenantA = Tenant::factory()->create(['name' => 'Acme Corporation']);
    $tenantB = Tenant::factory()->create(['name' => 'Global Logistics']);

    TenantSubscription::factory()->create(['tenant_id' => $tenantA->id]);
    TenantSubscription::factory()->create(['tenant_id' => $tenantB->id]);

    $response = $this->get(
        route('admin.subscriptions.index', ['search' => 'Acme'])
    );

    $response->assertOk();
});

it('preserves query strings across pagination', function () {
    actingAsSubscriptionSuperAdmin();

    $plan = Plan::factory()->create();

    TenantSubscription::factory()
        ->count(20)
        ->create(['plan_id' => $plan->id, 'status' => SubscriptionStatus::Active]);

    $response = $this->get(
        route('admin.subscriptions.index', [
            'status' => SubscriptionStatus::Active->value,
            'page' => 2,
        ])
    );

    $response->assertOk();
});

it('allows a superadmin to cancel an active subscription', function () {
    actingAsSubscriptionSuperAdmin();

    $subscription = TenantSubscription::factory()->create([
        'status' => SubscriptionStatus::Active,
        'canceled_at' => null,
    ]);

    $response = $this->post(
        route('admin.subscriptions.cancel', $subscription)
    );

    $response->assertRedirect();

    $subscription->refresh();
    expect($subscription->canceled_at)->not->toBeNull();
});
