<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\CustomerPayment;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

function createCustomerUiTestTenant(array $settings = []): Tenant
{
    return app(CreateTenantAction::class)->execute(
        new CreateTenantData(
            name: 'Customer UI Test Org',
            slug: 'customer-ui-test-org-'.uniqid(),
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

function createCustomerUiAuthUser(Tenant $tenant): User
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

it('renders customers index inertia page with stats and filters', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Starlight Enterprise',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->get('/admin/accounting/customers');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Customers/Index')
        ->has('customers.data', 1)
        ->has('stats')
        ->has('filters')
    );
});

it('renders customers create inertia page with chart of accounts', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/customers/create');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Customers/Create')
        ->has('accounts')
    );
});

it('creates a customer via web post and redirects to show page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $response = $this->actingAs($user)->post('/admin/accounting/customers', [
        'name' => 'Apex Solutions',
        'customer_code' => 'CUST-APEX',
        'email' => 'apex@example.com',
        'phone' => '+60123456789',
        'credit_limit' => '10000.00',
        'payment_terms_days' => 30,
        'billing_country' => 'MY',
    ]);

    $customer = Customer::where('tenant_id', $tenant->id)->where('customer_code', 'CUST-APEX')->firstOrFail();

    $response->assertRedirect(route('admin.accounting.customers.show', $customer));
    expect($customer->name)->toBe('Apex Solutions');
});

it('renders sales invoices index inertia page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    SalesInvoice::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'grand_total' => '1200.00',
    ]);

    $response = $this->actingAs($user)->get('/admin/accounting/invoices');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Invoices/Index')
        ->has('invoices.data', 1)
        ->has('stats')
    );
});

it('renders sales invoices create inertia page with suggested invoice number', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/invoices/create');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Invoices/Create')
        ->has('customers')
        ->has('accounts')
        ->has('suggestedInvoiceNumber')
    );
});

it('creates and issues a sales invoice', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $customer = Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => 'active',
    ]);

    $revenueAccount = Account::where('tenant_id', $tenant->id)
        ->where('is_postable', true)
        ->first() ?? Account::create([
            'tenant_id' => $tenant->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'is_active' => true,
            'is_postable' => true,
        ]);

    $postResponse = $this->actingAs($user)->post('/admin/accounting/invoices', [
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-TEST-001',
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'currency' => 'MYR',
        'lines' => [
            [
                'description' => 'Consulting Services',
                'quantity' => 2,
                'unit_price' => 500,
                'discount_amount' => 0,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'subtotal' => 1000,
                'total' => 1000,
                'revenue_account_id' => $revenueAccount->id,
            ],
        ],
    ]);

    $invoice = SalesInvoice::where('tenant_id', $tenant->id)
        ->where('invoice_number', 'INV-TEST-001')
        ->firstOrFail();

    $postResponse->assertRedirect(route('admin.accounting.invoices.show', $invoice));
    expect((float) $invoice->grand_total)->toEqual(1000.0);
    expect($invoice->status)->toBe('draft');

    $issueResponse = $this->actingAs($user)->post("/admin/accounting/invoices/{$invoice->id}/issue");
    $issueResponse->assertRedirect();
    expect($invoice->fresh()->status)->toBe('issued');
});

it('renders customer payments index inertia page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $account = Account::where('tenant_id', $tenant->id)->where('is_postable', true)->first()
        ?? Account::create([
            'tenant_id' => $tenant->id,
            'code' => '1130',
            'name' => 'Bank Account',
            'is_active' => true,
            'is_postable' => true,
        ]);

    CustomerPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'bank_account_id' => $account->id,
        'amount' => '450.00',
    ]);

    $response = $this->actingAs($user)->get('/admin/accounting/payments');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Payments/Index')
        ->has('payments.data', 1)
        ->has('stats')
    );
});

it('renders receivable aging report inertia page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/reports/receivable-aging');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Reports/ReceivableAging')
        ->has('report.summary')
        ->has('report.customers')
        ->has('asOfDate')
    );
});

it('renders customer statement report inertia page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($user)->get("/admin/accounting/reports/customer-statement?customer_id={$customer->id}");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Reports/CustomerStatement')
        ->has('statement')
        ->has('customer')
        ->has('fromDate')
        ->has('toDate')
    );
});

it('renders reports center index page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/reports');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Reports/Index')
    );
});

it('renders trial balance report inertia page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/reports/trial-balance');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Reports/TrialBalance')
        ->has('trialBalance')
        ->has('fromDate')
        ->has('toDate')
    );
});

it('renders general ledger report inertia page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/reports/general-ledger');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Reports/GeneralLedger')
        ->has('accounts')
        ->has('fromDate')
        ->has('toDate')
    );
});

it('renders profit and loss financial statement inertia page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/statements/profit-and-loss');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Statements/ProfitAndLoss')
        ->has('statement')
        ->has('fromDate')
        ->has('toDate')
    );
});

it('renders balance sheet financial statement inertia page', function () {
    $tenant = createCustomerUiTestTenant();
    $user = createCustomerUiAuthUser($tenant);

    $response = $this->actingAs($user)->get('/admin/accounting/statements/balance-sheet');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounting/Statements/BalanceSheet')
        ->has('statement')
        ->has('fromDate')
        ->has('toDate')
    );
});
