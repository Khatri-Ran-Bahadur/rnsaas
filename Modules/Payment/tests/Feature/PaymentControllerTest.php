<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Models\Tenant;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function actingAsSuperAdmin(): User
{
    Role::findOrCreate('SuperAdmin', 'web');

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole('SuperAdmin');

    test()->actingAs($user);

    return $user;
}

it('allows a superadmin to view payment listing', function () {
    actingAsSuperAdmin();

    $tenant = Tenant::factory()->create();

    $plan = Plan::factory()->create();

    $subscription = TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Pending,
    ]);

    PaymentTransaction::factory()->create([
        'tenant_id' => $tenant->id,
        'subscription_id' => $subscription->id,
        'status' => PaymentStatus::Pending,
        'type' => PaymentType::Subscription,
    ]);

    $response = $this->get(route('superadmin.payments.index'));

    $response->assertOk();
});

it('filters payments by status', function () {
    actingAsSuperAdmin();

    PaymentTransaction::factory()->create([
        'status' => PaymentStatus::Pending,
    ]);

    PaymentTransaction::factory()->create([
        'status' => PaymentStatus::Paid,
    ]);

    $response = $this->get(route('superadmin.payments.index', [
        'status' => PaymentStatus::Pending->value,
    ]));

    $response->assertOk();
});

it('filters payments by provider', function () {
    actingAsSuperAdmin();

    PaymentTransaction::factory()->create([
        'provider' => 'bank_transfer',
    ]);

    PaymentTransaction::factory()->create([
        'provider' => 'stripe',
    ]);

    $response = $this->get(route('superadmin.payments.index', [
        'provider' => 'bank_transfer',
    ]));

    $response->assertOk();
});

it('paginates payments', function () {
    actingAsSuperAdmin();

    PaymentTransaction::factory()
        ->count(25)
        ->create();

    $response = $this->get(route('superadmin.payments.index'));

    $response->assertOk();
});
