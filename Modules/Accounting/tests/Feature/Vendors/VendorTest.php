<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Application\Actions\Vendors\CreateVendorAction;
use Modules\Accounting\Application\Actions\Vendors\ToggleVendorStatusAction;
use Modules\Accounting\Application\Actions\Vendors\UpdateVendorAction;
use Modules\Accounting\Application\DTOs\Vendors\CreateVendorData;
use Modules\Accounting\Application\DTOs\Vendors\UpdateVendorData;
use Modules\Accounting\Domain\Enums\VendorStatus;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Vendor;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

use Modules\Tenancy\Domain\Enums\TenantStatus;

function createTestTenant(string $name = 'Vendor Test Org', string $slug = 'vendor-test-org'): Tenant
{
    return app(CreateTenantAction::class)->execute(
        new CreateTenantData(
            name: $name,
            slug: $slug,
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

function createAuthUser(Tenant $tenant): User
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

it('can create a vendor', function () {
    $tenant = createTestTenant();
    $user = createAuthUser($tenant);
    $this->actingAs($user);

    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();

    $action = app(CreateVendorAction::class);
    $vendor = $action->execute(
        data: new CreateVendorData(
            vendorCode: 'VEND-001',
            name: 'Acme Supplies',
            email: 'supplies@acme.test',
            phone: '123456789',
            taxNumber: 'TAX-999',
            billingAddressLine1: '123 Market St',
            billingAddressLine2: null,
            billingCity: 'Kuala Lumpur',
            billingState: 'WP',
            billingPostcode: '50000',
            billingCountry: 'MY',
            payableAccountId: $payableAccount->id,
            creditLimit: '50000.000000',
            paymentTermsDays: 30,
        ),
        currentTenant: app(CurrentTenant::class),
    );

    expect($vendor)->toBeInstanceOf(Vendor::class)
        ->and($vendor->tenant_id)->toBe($tenant->id)
        ->and($vendor->vendor_code)->toBe('VEND-001')
        ->and($vendor->name)->toBe('Acme Supplies')
        ->and($vendor->payable_account_id)->toBe($payableAccount->id)
        ->and($vendor->status)->toBe(VendorStatus::ACTIVE);

    $this->assertDatabaseHas('accounting_vendors', [
        'id' => $vendor->id,
        'tenant_id' => $tenant->id,
        'vendor_code' => 'VEND-001',
        'name' => 'Acme Supplies',
    ]);
});

it('can update a vendor', function () {
    $tenant = createTestTenant();
    $user = createAuthUser($tenant);
    $this->actingAs($user);

    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Vendor Name',
    ]);

    $action = app(UpdateVendorAction::class);
    $updated = $action->execute(
        vendor: $vendor,
        data: new UpdateVendorData(
            name: 'Updated Vendor Name',
            email: 'updated@vendor.test',
            phone: '987654321',
            taxNumber: 'TAX-888',
            billingAddressLine1: '456 Business Way',
            billingAddressLine2: null,
            billingCity: 'Penang',
            billingState: 'PNG',
            billingPostcode: '10000',
            billingCountry: 'MY',
            payableAccountId: null,
            creditLimit: '20000.000000',
            paymentTermsDays: 45,
        ),
        currentTenant: app(CurrentTenant::class),
    );

    expect($updated->name)->toBe('Updated Vendor Name')
        ->and($updated->email)->toBe('updated@vendor.test')
        ->and($updated->payment_terms_days)->toBe(45);
});

it('can deactivate and activate a vendor', function () {
    $tenant = createTestTenant();
    $user = createAuthUser($tenant);
    $this->actingAs($user);

    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => VendorStatus::ACTIVE,
    ]);

    $action = app(ToggleVendorStatusAction::class);

    $toggled = $action->execute($vendor, app(CurrentTenant::class));
    expect($toggled->status)->toBe(VendorStatus::INACTIVE);

    $toggledAgain = $action->execute($toggled, app(CurrentTenant::class));
    expect($toggledAgain->status)->toBe(VendorStatus::ACTIVE);
});

it('blocks duplicate vendor code within the same tenant via HTTP request', function () {
    $tenant = createTestTenant();
    $user = createAuthUser($tenant);
    $this->actingAs($user);

    Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_code' => 'VEND-DUP',
    ]);

    $response = $this->postJson(route('admin.accounting.vendors.store'), [
        'vendor_code' => 'VEND-DUP',
        'name' => 'Another Vendor',
        'payment_terms_days' => 30,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['vendor_code']);
});

it('enforces tenant isolation for vendors', function () {
    $tenantA = createTestTenant('Tenant A', 'tenant-a');
    $tenantB = createTestTenant('Tenant B', 'tenant-b');

    $userA = createAuthUser($tenantA);
    $this->actingAs($userA);

    $vendorB = Vendor::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Vendor for B',
    ]);

    $response = $this->getJson(route('admin.accounting.vendors.show', $vendorB));
    $response->assertStatus(404);
});

it('blocks cross-tenant payable account assignment', function () {
    $tenantA = createTestTenant('Tenant A', 'tenant-a');
    $tenantB = createTestTenant('Tenant B', 'tenant-b');

    $userA = createAuthUser($tenantA);
    $this->actingAs($userA);

    $accountB = Account::where('tenant_id', $tenantB->id)->first();

    $response = $this->postJson(route('admin.accounting.vendors.store'), [
        'vendor_code' => 'VEND-CROSS',
        'name' => 'Cross Vendor',
        'payable_account_id' => $accountB->id,
        'payment_terms_days' => 30,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['payable_account_id']);
});
