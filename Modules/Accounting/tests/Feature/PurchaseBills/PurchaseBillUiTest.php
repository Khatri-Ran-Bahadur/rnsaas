<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\Vendor;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

function createBillUiTestTenant(): Tenant
{
    return app(CreateTenantAction::class)->execute(
        new CreateTenantData(
            name: 'Bill UI Test Org',
            slug: 'bill-ui-test-org-'.uniqid(),
            industry: 'services',
            status: TenantStatus::Active,
            countryCode: 'MY',
            timezone: 'Asia/Kuala_Lumpur',
            locale: 'en',
            currency: 'MYR',
            settings: [],
        )
    );
}

function createBillUiAuthUser(Tenant $tenant): User
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

it('renders purchase bills index inertia page', function () {
    $tenant = createBillUiTestTenant();
    $user = createBillUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/purchase-bills');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/PurchaseBills/Index')
        ->has('bills')
        ->has('stats')
    );
});

it('renders purchase bills create page and loads vendors without missing column errors', function () {
    $tenant = createBillUiTestTenant();
    $user = createBillUiAuthUser($tenant);

    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Acme Supplies Sdn Bhd',
        'status' => 'active',
    ]);

    $account = Account::where('tenant_id', $tenant->id)->where('is_postable', true)->first()
        ?? Account::create([
            'tenant_id' => $tenant->id,
            'code' => '5000',
            'name' => 'General Expense',
            'is_active' => true,
            'is_postable' => true,
        ]);

    $response = $this->actingAs($user)->get('/admin/accounting/purchase-bills/create');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/PurchaseBills/Create')
        ->has('vendors', 1)
        ->has('accounts')
        ->where('vendors.0.name', 'Acme Supplies Sdn Bhd')
    );
});

it('renders purchase bills edit page and loads vendors and bill details', function () {
    $tenant = createBillUiTestTenant();
    $user = createBillUiAuthUser($tenant);

    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Delta Global Logistics',
        'status' => 'active',
    ]);

    $bill = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user)->get("/admin/accounting/purchase-bills/{$bill->id}/edit");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/PurchaseBills/Edit')
        ->has('bill')
        ->has('vendors')
        ->where('vendors.0.name', 'Delta Global Logistics')
    );
});

it('renders vendor payments create page without missing column errors', function () {
    $tenant = createBillUiTestTenant();
    $user = createBillUiAuthUser($tenant);

    Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Apex Hardware',
        'status' => 'active',
    ]);

    Account::where('tenant_id', $tenant->id)->where('is_postable', true)->first()
        ?? Account::create([
            'tenant_id' => $tenant->id,
            'code' => '1130',
            'name' => 'Bank Account',
            'is_active' => true,
            'is_postable' => true,
        ]);

    $response = $this->actingAs($user)->get('/admin/accounting/vendor-payments/create');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/VendorPayments/Create')
        ->has('vendors', 1)
        ->has('bankAccounts')
        ->where('vendors.0.name', 'Apex Hardware')
    );
});
