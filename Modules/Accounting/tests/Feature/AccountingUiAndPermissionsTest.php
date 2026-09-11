<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\Vendor;
use Modules\Accounting\Models\VendorPayment;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

function createUiTestTenant(array $settings = []): Tenant
{
    return app(CreateTenantAction::class)->execute(
        new CreateTenantData(
            name: 'UI Test Org',
            slug: 'ui-test-org',
            industry: 'services',
            status: TenantStatus::Active,
            countryCode: 'MY',
            timezone: 'Asia/Kuala_Lumpur',
            locale: 'en',
            currency: 'MYR',
            settings: $settings,
        )
    );
}

function createUiAuthUser(Tenant $tenant): User
{
    $user = User::factory()->create();

    TenantMembership::firstOrCreate([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
    ], [
        'status' => 'active',
    ]);

    session(['current_tenant_id' => $tenant->id]);
    app(CurrentTenant::class)->set($tenant);

    return $user;
}

it('renders vendors index inertia page with stats and filters', function () {
    $tenant = createUiTestTenant();
    $user = createUiAuthUser($tenant);

    Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Acme Supplier',
    ]);

    $response = $this->actingAs($user)->get('/admin/accounting/vendors');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Vendors/Index')
        ->has('vendors.data', 1)
        ->has('stats')
        ->has('filters')
    );
});

it('renders purchase bills index inertia page', function () {
    $tenant = createUiTestTenant();
    $user = createUiAuthUser($tenant);

    $vendor = Vendor::factory()->create(['tenant_id' => $tenant->id]);
    PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'grand_total' => '500.00',
    ]);

    $response = $this->actingAs($user)->get('/admin/accounting/purchase-bills');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/PurchaseBills/Index')
        ->has('bills.data', 1)
        ->has('stats')
    );
});

it('renders vendor payments index inertia page', function () {
    $tenant = createUiTestTenant();
    $user = createUiAuthUser($tenant);

    $vendor = Vendor::factory()->create(['tenant_id' => $tenant->id]);
    $account = Account::where('tenant_id', $tenant->id)->where('is_postable', true)->first()
        ?? Account::create([
            'tenant_id' => $tenant->id,
            'code' => '1130',
            'name' => 'Bank Account',
            'is_active' => true,
            'is_postable' => true,
        ]);

    VendorPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bank_account_id' => $account->id,
        'amount' => '250.00',
    ]);

    $response = $this->actingAs($user)->get('/admin/accounting/vendor-payments');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/VendorPayments/Index')
        ->has('payments.data', 1)
        ->has('stats')
    );
});

it('renders payable aging report inertia page', function () {
    $tenant = createUiTestTenant();
    $user = createUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/reports/payable-aging');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Reports/PayableAging')
        ->has('report.summary')
        ->has('report.vendors')
        ->has('asOfDate')
    );
});

it('blocks access when accounting module is disabled in tenant settings', function () {
    $tenant = createUiTestTenant(settings: [
        'modules' => [
            'accounting' => false,
        ],
    ]);
    $user = createUiAuthUser($tenant);

    // JSON request gets 403
    $jsonResponse = $this->actingAs($user)
        ->getJson('/admin/accounting/vendors');
    $jsonResponse->assertStatus(403);

    // Web request gets redirected to admin dashboard with error flash
    $webResponse = $this->actingAs($user)
        ->get('/admin/accounting/vendors');
    $webResponse->assertRedirect(route('admin.dashboard'));
    $webResponse->assertSessionHas('error');
});
