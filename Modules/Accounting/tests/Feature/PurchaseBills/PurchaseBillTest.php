<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Application\Actions\AccountingPeriod\GenerateAccountingPeriodsAction;
use Modules\Accounting\Application\Actions\FiscalYear\CreateFiscalYearAction;
use Modules\Accounting\Application\Actions\PurchaseBills\CreatePurchaseBillAction;
use Modules\Accounting\Application\Actions\PurchaseBills\IssuePurchaseBillAction;
use Modules\Accounting\Application\Actions\PurchaseBills\PostPurchaseBillAction;
use Modules\Accounting\Application\Actions\PurchaseBills\UpdatePurchaseBillAction;
use Modules\Accounting\Application\Actions\PurchaseBills\VoidPurchaseBillAction;
use Modules\Accounting\Application\DTOs\PurchaseBills\CreatePurchaseBillData;
use Modules\Accounting\Application\DTOs\PurchaseBills\PurchaseBillLineData;
use Modules\Accounting\Domain\Enums\PurchaseBillStatus;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\Vendor;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function createBillTestTenant(string $name = 'Bill Test Org', string $slug = 'bill-test-org'): Tenant
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

function createBillFiscalYear(Tenant $tenant): FiscalYear
{
    $fiscalYear = app(CreateFiscalYearAction::class)->execute(
        tenant: $tenant,
        code: 'FY2026',
        name: 'Fiscal Year 2026',
        startDate: CarbonImmutable::parse('2026-01-01'),
        endDate: CarbonImmutable::parse('2026-12-31'),
        isCurrent: true,
    );

    app(GenerateAccountingPeriodsAction::class)->execute($fiscalYear);

    return $fiscalYear;
}

function createBillAuthUser(Tenant $tenant): User
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

it('can create a purchase bill and calculate totals with server-side tax calculation', function () {
    $tenant = createBillTestTenant();
    $user = createBillAuthUser($tenant);
    $this->actingAs($user);

    $expenseAccount = Account::where('tenant_id', $tenant->id)->where('code', '5100')->first();
    $taxAccount = Account::where('tenant_id', $tenant->id)->where('code', '1150')->first(); // Input tax/Asset/Tax
    if (! $taxAccount) {
        $taxAccount = Account::where('tenant_id', $tenant->id)->where('code', '1110')->first();
    }

    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();
    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $action = app(CreatePurchaseBillAction::class);
    $bill = $action->execute(
        data: new CreatePurchaseBillData(
            vendorId: $vendor->id,
            billNumber: 'PB-2026-001',
            billDate: new DateTime('2026-02-01'),
            dueDate: new DateTime('2026-03-03'),
            currency: 'MYR',
            reference: 'PO-100',
            notes: 'Office supplies',
            lines: [
                new PurchaseBillLineData(
                    lineNumber: 1,
                    description: 'Desks and Chairs',
                    quantity: '10.000000',
                    unitPrice: '100.000000',
                    discountAmount: '50.000000', // subtotal = 1000 - 50 = 950
                    taxRate: '10.000000',       // 10% of 950 = 95
                    taxAmount: '0.000000',      // Client sent 0; server must calculate 95.000000
                    subtotal: '0.000000',
                    total: '0.000000',
                    debitAccountId: $expenseAccount->id,
                    taxAccountId: $taxAccount->id,
                ),
            ],
        ),
        currentTenant: app(CurrentTenant::class),
    );

    expect($bill->status)->toBe(PurchaseBillStatus::DRAFT)
        ->and(bccomp((string) $bill->subtotal, '950.000000', 6))->toBe(0)
        ->and(bccomp((string) $bill->discount_total, '50.000000', 6))->toBe(0)
        ->and(bccomp((string) $bill->tax_total, '95.000000', 6))->toBe(0)
        ->and(bccomp((string) $bill->grand_total, '1045.000000', 6))->toBe(0);

    $line = $bill->lines->first();
    expect(bccomp((string) $line->tax_amount, '95.000000', 6))->toBe(0)
        ->and(bccomp((string) $line->total, '1045.000000', 6))->toBe(0);
});

it('can update a draft purchase bill', function () {
    $tenant = createBillTestTenant();
    $user = createBillAuthUser($tenant);
    $this->actingAs($user);

    $expenseAccount = Account::where('tenant_id', $tenant->id)->where('code', '5100')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();

    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $bill = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'status' => PurchaseBillStatus::DRAFT,
    ]);

    $action = app(UpdatePurchaseBillAction::class);
    $updated = $action->execute(
        bill: $bill,
        data: new CreatePurchaseBillData(
            vendorId: $vendor->id,
            billNumber: 'PB-UPDATED',
            billDate: new DateTime('2026-02-10'),
            dueDate: new DateTime('2026-03-10'),
            currency: 'MYR',
            reference: 'REF-UPDATED',
            notes: 'Updated notes',
            lines: [
                new PurchaseBillLineData(
                    lineNumber: 1,
                    description: 'Updated Item',
                    quantity: '5.000000',
                    unitPrice: '200.000000',
                    discountAmount: '0.000000',
                    taxRate: '0.000000',
                    taxAmount: '0.000000',
                    subtotal: '1000.000000',
                    total: '1000.000000',
                    debitAccountId: $expenseAccount->id,
                    taxAccountId: null,
                ),
            ],
        ),
        currentTenant: app(CurrentTenant::class),
    );

    expect($updated->bill_number)->toBe('PB-UPDATED')
        ->and(bccomp((string) $updated->grand_total, '1000.000000', 6))->toBe(0)
        ->and($updated->lines)->toHaveCount(1);
});

it('can issue a draft purchase bill', function () {
    $tenant = createBillTestTenant();
    $user = createBillAuthUser($tenant);
    $this->actingAs($user);

    $expenseAccount = Account::where('tenant_id', $tenant->id)->where('code', '5100')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();
    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $bill = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'status' => PurchaseBillStatus::DRAFT,
    ]);

    $bill->lines()->create([
        'tenant_id' => $tenant->id,
        'line_number' => 1,
        'description' => 'Test line',
        'quantity' => '1.000000',
        'unit_price' => '100.000000',
        'discount_amount' => '0.000000',
        'tax_rate' => '0.000000',
        'tax_amount' => '0.000000',
        'subtotal' => '100.000000',
        'total' => '100.000000',
        'debit_account_id' => $expenseAccount->id,
    ]);

    $issued = app(IssuePurchaseBillAction::class)->execute($bill, app(CurrentTenant::class));
    expect($issued->status)->toBe(PurchaseBillStatus::ISSUED);
});

it('can post an issued bill with balanced journal entry and idempotent re-posting', function () {
    $tenant = createBillTestTenant();
    createBillFiscalYear($tenant);
    $user = createBillAuthUser($tenant);
    $this->actingAs($user);

    $expenseAccount = Account::where('tenant_id', $tenant->id)->where('code', '5100')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();
    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $bill = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bill_number' => 'PB-POST-1',
        'bill_date' => '2026-02-15',
        'due_date' => '2026-03-15',
        'subtotal' => '500.000000',
        'discount_total' => '0.000000',
        'tax_total' => '0.000000',
        'grand_total' => '500.000000',
        'status' => PurchaseBillStatus::ISSUED,
    ]);

    $bill->lines()->create([
        'tenant_id' => $tenant->id,
        'line_number' => 1,
        'description' => 'Test line',
        'quantity' => '5.000000',
        'unit_price' => '100.000000',
        'discount_amount' => '0.000000',
        'tax_rate' => '0.000000',
        'tax_amount' => '0.000000',
        'subtotal' => '500.000000',
        'total' => '500.000000',
        'debit_account_id' => $expenseAccount->id,
    ]);

    $postAction = app(PostPurchaseBillAction::class);
    $postedBill = $postAction->execute($bill, app(CurrentTenant::class));

    expect($postedBill->status)->toBe(PurchaseBillStatus::POSTED)
        ->and($postedBill->journal_entry_id)->not->toBeNull();

    $journal = JournalEntry::with('lines')->find($postedBill->journal_entry_id);
    expect($journal)->not->toBeNull()
        ->and($journal->status->value)->toBe('posted')
        ->and($journal->lines)->toHaveCount(2);

    $debitSum = $journal->lines->where('line_type.value', 'debit')->sum('amount');
    $creditSum = $journal->lines->where('line_type.value', 'credit')->sum('amount');
    expect(bccomp((string) $debitSum, (string) $creditSum, 6))->toBe(0);

    // Idempotent test: posting the same bill again must return without duplicating journals
    $journalCountBefore = JournalEntry::where('tenant_id', $tenant->id)->count();
    $repostedBill = $postAction->execute($postedBill, app(CurrentTenant::class));

    expect($repostedBill->id)->toBe($postedBill->id)
        ->and(JournalEntry::where('tenant_id', $tenant->id)->count())->toBe($journalCountBefore);
});

it('can void an issued bill, but blocks voiding posted bills directly', function () {
    $tenant = createBillTestTenant();
    $user = createBillAuthUser($tenant);
    $this->actingAs($user);

    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => 'active',
    ]);

    $issuedBill = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'status' => PurchaseBillStatus::ISSUED,
    ]);

    $voidAction = app(VoidPurchaseBillAction::class);
    $voided = $voidAction->execute($issuedBill, app(CurrentTenant::class));
    expect($voided->status)->toBe(PurchaseBillStatus::VOID);

    $postedBill = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'status' => PurchaseBillStatus::POSTED,
    ]);

    expect(fn () => $voidAction->execute($postedBill, app(CurrentTenant::class)))
        ->toThrow(HttpException::class);
});

it('blocks cross-tenant account in bill creation', function () {
    $tenantA = createBillTestTenant('Tenant A', 'tenant-a');
    $tenantB = createBillTestTenant('Tenant B', 'tenant-b');

    $userA = createBillAuthUser($tenantA);
    $this->actingAs($userA);

    $payableAccountA = Account::where('tenant_id', $tenantA->id)->where('code', '2110')->first();
    $vendorA = Vendor::factory()->create([
        'tenant_id' => $tenantA->id,
        'payable_account_id' => $payableAccountA->id,
        'status' => 'active',
    ]);

    $accountB = Account::where('tenant_id', $tenantB->id)->where('code', '5100')->first();

    $response = $this->postJson(route('admin.accounting.purchase-bills.store'), [
        'vendor_id' => $vendorA->id,
        'bill_number' => 'PB-CROSS-ACC',
        'bill_date' => '2026-02-01',
        'due_date' => '2026-03-01',
        'currency' => 'MYR',
        'lines' => [
            [
                'description' => 'Cross tenant item',
                'quantity' => 1,
                'unit_price' => 100,
                'debit_account_id' => $accountB->id,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['lines.0.debit_account_id']);
});

it('blocks cross-tenant vendor in bill creation', function () {
    $tenantA = createBillTestTenant('Tenant A', 'tenant-a');
    $tenantB = createBillTestTenant('Tenant B', 'tenant-b');

    $userA = createBillAuthUser($tenantA);
    $this->actingAs($userA);

    $expenseAccountA = Account::where('tenant_id', $tenantA->id)->where('code', '5000')->first();
    $vendorB = Vendor::factory()->create([
        'tenant_id' => $tenantB->id,
        'status' => 'active',
    ]);

    $response = $this->postJson(route('admin.accounting.purchase-bills.store'), [
        'vendor_id' => $vendorB->id,
        'bill_number' => 'PB-CROSS-VEND',
        'bill_date' => '2026-02-01',
        'due_date' => '2026-03-01',
        'currency' => 'MYR',
        'lines' => [
            [
                'description' => 'Cross vendor item',
                'quantity' => 1,
                'unit_price' => 100,
                'debit_account_id' => $expenseAccountA->id,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['vendor_id']);
});
