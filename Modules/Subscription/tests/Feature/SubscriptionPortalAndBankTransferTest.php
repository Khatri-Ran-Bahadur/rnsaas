<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\SubscriptionBankTransfer;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Domain\Enums\BranchStatus;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');

    // Create SuperAdmin and Admin roles if not present
    Role::firstOrCreate(['name' => 'SuperAdmin']);
    Role::firstOrCreate(['name' => 'Admin']);

    // Seed a plan
    $this->plan = Plan::query()->firstOrCreate(
        ['slug' => 'test-enterprise'],
        [
            'public_id' => '01MTESTPLAN1234567890ABCDEF',
            'name' => 'Enterprise Pro',
            'description' => 'Enterprise plan for testing',
            'price' => 100.00,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'trial_days' => 14,
            'included_branches' => 1,
            'extra_branch_price' => 20.00,
            'is_active' => true,
            'sort_order' => 1,
        ]
    );

    // Create tenant and admin user
    $this->tenant = Tenant::factory()->create([
        'status' => TenantStatus::Active,
        'industry' => 'technology',
    ]);

    $this->user = User::factory()->create();
    $this->membership = TenantMembership::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'status' => TenantMembershipStatus::Active,
    ]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('SuperAdmin');
});

test('tenant can view subscription portal index', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get(route('admin.subscription.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Subscription/Portal/Index'));
});

test('tenant can view subscription plans with dynamic branch pricing', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->get(route('admin.subscription.plans'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Subscription/Portal/Plans')
        ->has('plans')
    );
});

test('tenant can submit bank transfer request with extra branches', function () {
    $receipt = UploadedFile::fake()->create('payment_slip.jpg', 200, 'image/jpeg');

    $response = $this->actingAs($this->user)
        ->withSession(['current_tenant_id' => $this->tenant->id])
        ->post(route('admin.subscription.bank-transfer'), [
            'plan_id' => $this->plan->id,
            'branches_count' => 3, // 1 included + 2 extra ($20 * 2 = $40 extra, total $140)
            'transaction_reference' => 'TXN-BANK-123456',
            'receipt' => $receipt,
            'notes' => 'Transferred via wire transfer',
        ]);

    $response->assertRedirect(route('admin.subscription.index'));

    $this->assertDatabaseHas('subscription_bank_transfers', [
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'branches_count' => 3,
        'base_amount' => '100.00',
        'extra_branches_amount' => '40.00',
        'total_amount' => '140.00',
        'transaction_reference' => 'TXN-BANK-123456',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('tenant_subscriptions', [
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'allowed_branches' => 3,
        'status' => SubscriptionStatus::Pending->value,
    ]);
});

test('superadmin can approve bank transfer and activate tenant subscription with branch count', function () {
    $transfer = SubscriptionBankTransfer::create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'plan_id' => $this->plan->id,
        'branches_count' => 3,
        'base_amount' => 100.00,
        'extra_branches_amount' => 40.00,
        'total_amount' => 140.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'transaction_reference' => 'REF-TO-APPROVE',
        'receipt_path' => 'receipts/test.jpg',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->post(route('superadmin.subscriptions.bank-transfers.approve', $transfer));

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('subscription_bank_transfers', [
        'id' => $transfer->id,
        'status' => 'approved',
        'approved_by' => $this->superAdmin->id,
    ]);

    $this->assertDatabaseHas('tenant_subscriptions', [
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'allowed_branches' => 3,
        'status' => SubscriptionStatus::Active->value,
    ]);
});

test('superadmin can reject bank transfer with reason', function () {
    $transfer = SubscriptionBankTransfer::create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'plan_id' => $this->plan->id,
        'branches_count' => 1,
        'base_amount' => 100.00,
        'extra_branches_amount' => 0,
        'total_amount' => 100.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'transaction_reference' => 'REF-TO-REJECT',
        'receipt_path' => 'receipts/test.jpg',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->post(route('superadmin.subscriptions.bank-transfers.reject', $transfer), [
            'rejection_reason' => 'Receipt is illegible. Please resubmit.',
        ]);

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('subscription_bank_transfers', [
        'id' => $transfer->id,
        'status' => 'rejected',
        'rejection_reason' => 'Receipt is illegible. Please resubmit.',
    ]);
});

test('branch limit is enforced based on subscription allowed branches', function () {
    // Active subscription with only 1 allowed branch
    TenantSubscription::create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'status' => SubscriptionStatus::Active,
        'allowed_branches' => 1,
        'starts_at' => now(),
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonth(),
    ]);

    // Create 1 branch (allowed)
    Branch::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'HQ Branch',
        'code' => 'HQ01',
        'status' => BranchStatus::Active,
    ]);

    // Attempt to create second branch (exceeds 1 allowed branch)
    $response = $this->actingAs($this->user)
        ->from('/admin/branches/create')
        ->post('/admin/branches', [
            'name' => 'Second Branch',
            'code' => 'HQ02',
            'status' => 'active',
        ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseMissing('branches', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Second Branch',
    ]);
});

test('unsubscribed tenant accessing protected admin route is redirected to subscription plans', function () {
    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Enforce-Subscription-Test' => 'true'])
        ->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.subscription.plans'));
});

test('tenant can submit bank transfer for lifetime plan without timestamp overflow error', function () {
    $lifetimePlan = Plan::create([
        'public_id' => (string) Str::ulid(),
        'name' => 'Enterprise Lifetime',
        'slug' => 'enterprise-lifetime-test',
        'price' => 999.00,
        'currency' => 'USD',
        'billing_cycle' => 'lifetime',
        'is_active' => true,
        'included_branches' => 1,
        'extra_branch_price' => 20.00,
    ]);

    $file = UploadedFile::fake()->create('receipt.pdf', 500, 'application/pdf');

    $response = $this->actingAs($this->user)
        ->post(route('admin.subscription.bank-transfer'), [
            'plan_id' => $lifetimePlan->id,
            'branches_count' => 1,
            'transaction_reference' => 'TXN-LIFETIME-001',
            'receipt' => $file,
            'notes' => 'Paid via wire transfer',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.subscription.index'));

    $transfer = SubscriptionBankTransfer::where('transaction_reference', 'TXN-LIFETIME-001')->first();
    expect($transfer)->not->toBeNull();
    expect($transfer->billing_cycle)->toBe('lifetime');

    // Verify tenant subscription was created with null current_period_ends_at
    $sub = TenantSubscription::where('tenant_id', $this->tenant->id)->first();
    expect($sub)->not->toBeNull();
    expect($sub->status)->toBe(SubscriptionStatus::Pending);
    expect($sub->current_period_ends_at)->toBeNull();

    // Verify SuperAdmin can approve it without error
    $superadmin = User::factory()->create();
    $superadmin->assignRole('SuperAdmin');

    $approveResponse = $this->actingAs($superadmin)
        ->post(route('superadmin.subscriptions.bank-transfers.approve', $transfer));

    $approveResponse->assertSessionHas('success');
    $sub->refresh();
    expect($sub->status)->toBe(SubscriptionStatus::Active);
    expect($sub->current_period_ends_at)->toBeNull();
});
