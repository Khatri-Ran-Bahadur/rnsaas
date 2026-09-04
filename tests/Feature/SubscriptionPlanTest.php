<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Subscription\Models\Feature;
use Modules\Subscription\Models\Plan;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createSuperAdminUser(): User
{
    $role = Role::firstOrCreate(['name' => 'SuperAdmin']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('superadmin can access subscription plans index', function () {
    $admin = createSuperAdminUser();

    $response = $this->actingAs($admin)->get('/admin/subscriptions/plans');

    $response->assertOk();
});

test('superadmin can access create plan page', function () {
    $admin = createSuperAdminUser();

    $response = $this->actingAs($admin)->get('/admin/subscriptions/plans/create');

    $response->assertOk();
});

test('superadmin can create a new plan with features', function () {
    $admin = createSuperAdminUser();

    $feature1 = Feature::create([
        'public_id' => (string) str()->ulid(),
        'name' => 'Invoices',
        'slug' => 'accounting.invoices',
        'module' => 'accounting',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->post('/admin/subscriptions/plans', [
        'name' => 'Pro Plan',
        'slug' => 'pro-plan',
        'description' => 'Pro plan description',
        'price' => 79.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'trial_days' => 14,
        'is_active' => true,
        'sort_order' => 4,
        'feature_ids' => [$feature1->id],
    ]);

    $response->assertRedirect('/superadmin/subscriptions/plans');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('subscription_plans', [
        'name' => 'Pro Plan',
        'slug' => 'pro-plan',
        'price' => 79.00,
    ]);
});

test('superadmin can access edit plan page and update it', function () {
    $admin = createSuperAdminUser();

    $plan = Plan::create([
        'public_id' => (string) str()->ulid(),
        'name' => 'Old Plan Name',
        'slug' => 'old-plan-name',
        'description' => 'Old description',
        'price' => 29.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'trial_days' => 7,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($admin)->get("/admin/subscriptions/plans/{$plan->id}/edit");
    $response->assertOk();

    $updateResponse = $this->actingAs($admin)->put("/admin/subscriptions/plans/{$plan->id}", [
        'name' => 'Updated Plan Name',
        'slug' => 'old-plan-name',
        'description' => 'New description',
        'price' => 39.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'trial_days' => 14,
        'is_active' => true,
        'sort_order' => 1,
        'feature_ids' => [],
    ]);

    $updateResponse->assertRedirect('/superadmin/subscriptions/plans');
    $updateResponse->assertSessionHas('success');

    $this->assertDatabaseHas('subscription_plans', [
        'id' => $plan->id,
        'name' => 'Updated Plan Name',
        'price' => 39.00,
    ]);
});
