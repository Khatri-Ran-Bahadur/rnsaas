<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Application\Actions\AccountingPeriod\GenerateAccountingPeriodsAction;
use Modules\Accounting\Application\Actions\FiscalYear\CreateFiscalYearAction;
use Modules\Accounting\Application\Actions\Reports\GeneratePayableAgingAction;
use Modules\Accounting\Application\Actions\Reports\GenerateVendorBalanceAction;
use Modules\Accounting\Application\Actions\Reports\GenerateVendorStatementAction;
use Modules\Accounting\Application\Actions\VendorPayments\AllocateVendorPaymentAction;
use Modules\Accounting\Application\Actions\VendorPayments\CreateVendorPaymentAction;
use Modules\Accounting\Application\Actions\VendorPayments\PostVendorPaymentAction;
use Modules\Accounting\Application\DTOs\VendorPayments\CreateVendorPaymentData;
use Modules\Accounting\Domain\Enums\PurchaseBillStatus;
use Modules\Accounting\Domain\Enums\VendorPaymentStatus;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\Vendor;
use Modules\Accounting\Models\VendorPayment;
use Modules\Accounting\Models\VendorPaymentAllocation;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function createPaymentTestTenant(string $name = 'Payment Test Org', string $slug = 'payment-test-org'): Tenant
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

function createPaymentFiscalYear(Tenant $tenant): FiscalYear
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

function createPaymentAuthUser(Tenant $tenant): User
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

it('can create a draft vendor payment', function () {
    $tenant = createPaymentTestTenant();
    $user = createPaymentAuthUser($tenant);
    $this->actingAs($user);

    $bankAccount = Account::where('tenant_id', $tenant->id)->where('code', '1130')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();
    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $action = app(CreateVendorPaymentAction::class);
    $payment = $action->execute(
        data: new CreateVendorPaymentData(
            vendorId: $vendor->id,
            paymentNumber: 'VP-2026-001',
            paymentDate: new DateTime('2026-02-15'),
            amount: '1000.000000',
            currency: 'MYR',
            bankAccountId: $bankAccount->id,
            paymentMethod: 'bank_transfer',
            reference: 'TXN-001',
            notes: 'Payment for supplies',
        ),
        currentTenant: app(CurrentTenant::class),
    );

    expect($payment)->toBeInstanceOf(VendorPayment::class)
        ->and($payment->tenant_id)->toBe($tenant->id)
        ->and($payment->vendor_id)->toBe($vendor->id)
        ->and($payment->bank_account_id)->toBe($bankAccount->id)
        ->and(bccomp((string) $payment->amount, '1000.000000', 6))->toBe(0)
        ->and($payment->status)->toBe(VendorPaymentStatus::DRAFT);
});

it('blocks creating vendor payment with bank account from another tenant', function () {
    $tenantA = createPaymentTestTenant('Tenant A', 'tenant-a');
    $tenantB = createPaymentTestTenant('Tenant B', 'tenant-b');

    $userA = createPaymentAuthUser($tenantA);
    $this->actingAs($userA);

    $payableAccountA = Account::where('tenant_id', $tenantA->id)->where('code', '2110')->first();
    $vendorA = Vendor::factory()->create([
        'tenant_id' => $tenantA->id,
        'payable_account_id' => $payableAccountA->id,
        'status' => 'active',
    ]);

    $bankAccountB = Account::where('tenant_id', $tenantB->id)->where('code', '1130')->first();

    $response = $this->postJson(route('admin.accounting.vendor-payments.store'), [
        'vendor_id' => $vendorA->id,
        'payment_number' => 'VP-CROSS',
        'payment_date' => '2026-02-15',
        'amount' => 500,
        'currency' => 'MYR',
        'bank_account_id' => $bankAccountB->id,
        'payment_method' => 'bank_transfer',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['bank_account_id']);
});

it('can allocate payment partially and fully, and allows modifying existing allocation', function () {
    $tenant = createPaymentTestTenant();
    $user = createPaymentAuthUser($tenant);
    $this->actingAs($user);

    $bankAccount = Account::where('tenant_id', $tenant->id)->where('code', '1130')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();
    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $bill = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'grand_total' => '1000.000000',
        'status' => PurchaseBillStatus::ISSUED,
    ]);

    $payment = VendorPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bank_account_id' => $bankAccount->id,
        'amount' => '1000.000000',
        'status' => VendorPaymentStatus::DRAFT,
    ]);

    $action = app(AllocateVendorPaymentAction::class);

    // Partial allocation: RM 400
    $allocated = $action->execute(
        payment: $payment,
        allocations: [
            [
                'purchase_bill_id' => $bill->id,
                'amount' => '400.000000',
            ],
        ],
        currentTenant: app(CurrentTenant::class),
    );

    expect($allocated->allocations)->toHaveCount(1)
        ->and(bccomp((string) $allocated->allocations->first()->allocated_amount, '400.000000', 6))->toBe(0);

    // Re-allocation / Update on same payment: increase to RM 600
    // Must exclude the current payment's existing RM 400 so it doesn't fail!
    $updatedAllocation = $action->execute(
        payment: $payment,
        allocations: [
            [
                'purchase_bill_id' => $bill->id,
                'amount' => '600.000000',
            ],
        ],
        currentTenant: app(CurrentTenant::class),
    );

    expect($updatedAllocation->allocations)->toHaveCount(1)
        ->and(bccomp((string) $updatedAllocation->allocations->first()->allocated_amount, '600.000000', 6))->toBe(0);
});

it('blocks over-allocation and negative or zero allocation amounts', function () {
    $tenant = createPaymentTestTenant();
    $user = createPaymentAuthUser($tenant);
    $this->actingAs($user);

    $bankAccount = Account::where('tenant_id', $tenant->id)->where('code', '1130')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();
    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $bill = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'grand_total' => '500.000000',
        'status' => PurchaseBillStatus::ISSUED,
    ]);

    $payment = VendorPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bank_account_id' => $bankAccount->id,
        'amount' => '400.000000',
        'status' => VendorPaymentStatus::DRAFT,
    ]);

    $action = app(AllocateVendorPaymentAction::class);

    // Zero amount blocked
    expect(fn () => $action->execute(
        payment: $payment,
        allocations: [['purchase_bill_id' => $bill->id, 'amount' => '0']],
        currentTenant: app(CurrentTenant::class),
    ))->toThrow(HttpException::class);

    // Negative amount blocked
    expect(fn () => $action->execute(
        payment: $payment,
        allocations: [['purchase_bill_id' => $bill->id, 'amount' => '-50']],
        currentTenant: app(CurrentTenant::class),
    ))->toThrow(HttpException::class);

    // Exceeds payment amount (bill has 500, but payment is only 400; allocating 450 exceeds payment amount)
    expect(fn () => $action->execute(
        payment: $payment,
        allocations: [['purchase_bill_id' => $bill->id, 'amount' => '450.000000']],
        currentTenant: app(CurrentTenant::class),
    ))->toThrow(HttpException::class);

    // Exceeds bill outstanding (payment is 1000, bill is 500; allocating 600 exceeds bill outstanding)
    $largePayment = VendorPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bank_account_id' => $bankAccount->id,
        'amount' => '1000.000000',
        'status' => VendorPaymentStatus::DRAFT,
    ]);

    expect(fn () => $action->execute(
        payment: $largePayment,
        allocations: [['purchase_bill_id' => $bill->id, 'amount' => '600.000000']],
        currentTenant: app(CurrentTenant::class),
    ))->toThrow(HttpException::class);
});

it('blocks allocating to a bill belonging to a different vendor or tenant', function () {
    $tenantA = createPaymentTestTenant('Tenant A', 'tenant-a');
    $tenantB = createPaymentTestTenant('Tenant B', 'tenant-b');

    $userA = createPaymentAuthUser($tenantA);
    $this->actingAs($userA);

    $bankAccountA = Account::where('tenant_id', $tenantA->id)->where('code', '1130')->first();
    $vendorA1 = Vendor::factory()->create(['tenant_id' => $tenantA->id, 'status' => 'active']);
    $vendorA2 = Vendor::factory()->create(['tenant_id' => $tenantA->id, 'status' => 'active']);

    $billVendor2 = PurchaseBill::factory()->create([
        'tenant_id' => $tenantA->id,
        'vendor_id' => $vendorA2->id,
        'grand_total' => '500.000000',
        'status' => PurchaseBillStatus::ISSUED,
    ]);

    $paymentVendor1 = VendorPayment::factory()->create([
        'tenant_id' => $tenantA->id,
        'vendor_id' => $vendorA1->id,
        'bank_account_id' => $bankAccountA->id,
        'amount' => '500.000000',
        'status' => VendorPaymentStatus::DRAFT,
    ]);

    $action = app(AllocateVendorPaymentAction::class);

    // Wrong vendor blocked
    expect(fn () => $action->execute(
        payment: $paymentVendor1,
        allocations: [['purchase_bill_id' => $billVendor2->id, 'amount' => '100']],
        currentTenant: app(CurrentTenant::class),
    ))->toThrow(ModelNotFoundException::class);

    // Wrong tenant bill blocked
    $billTenantB = PurchaseBill::factory()->create([
        'tenant_id' => $tenantB->id,
        'grand_total' => '500.000000',
        'status' => PurchaseBillStatus::ISSUED,
    ]);

    expect(fn () => $action->execute(
        payment: $paymentVendor1,
        allocations: [['purchase_bill_id' => $billTenantB->id, 'amount' => '100']],
        currentTenant: app(CurrentTenant::class),
    ))->toThrow(ModelNotFoundException::class);
});

it('can post payment, generates balanced journal entry, and prevents duplicate posting', function () {
    $tenant = createPaymentTestTenant();
    createPaymentFiscalYear($tenant);
    $user = createPaymentAuthUser($tenant);
    $this->actingAs($user);

    $bankAccount = Account::where('tenant_id', $tenant->id)->where('code', '1130')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();
    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $payment = VendorPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'payment_number' => 'VP-POST-01',
        'payment_date' => '2026-02-20',
        'amount' => '750.000000',
        'bank_account_id' => $bankAccount->id,
        'status' => VendorPaymentStatus::DRAFT,
    ]);

    $postAction = app(PostVendorPaymentAction::class);
    $posted = $postAction->execute($payment, app(CurrentTenant::class));

    expect($posted->status)->toBe(VendorPaymentStatus::POSTED)
        ->and($posted->journal_entry_id)->not->toBeNull();

    $journal = JournalEntry::with('lines')->find($posted->journal_entry_id);
    expect($journal)->not->toBeNull()
        ->and($journal->status->value)->toBe('posted')
        ->and($journal->lines)->toHaveCount(2);

    $debitSum = $journal->lines->where('line_type.value', 'debit')->sum('amount');
    $creditSum = $journal->lines->where('line_type.value', 'credit')->sum('amount');
    expect(bccomp((string) $debitSum, (string) $creditSum, 6))->toBe(0);

    // Duplicate posting prevented
    $journalCountBefore = JournalEntry::where('tenant_id', $tenant->id)->count();
    $reposted = $postAction->execute($posted, app(CurrentTenant::class));
    expect($reposted->id)->toBe($posted->id)
        ->and(JournalEntry::where('tenant_id', $tenant->id)->count())->toBe($journalCountBefore);
});

it('calculates vendor balance based on AP subledger specific to that vendor', function () {
    $tenant = createPaymentTestTenant();
    $user = createPaymentAuthUser($tenant);
    $this->actingAs($user);

    $bankAccount = Account::where('tenant_id', $tenant->id)->where('code', '1130')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();

    $vendor1 = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $vendor2 = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    // Vendor 1 has posted bill of RM 1000 and posted payment of RM 300 -> balance RM 700
    PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor1->id,
        'grand_total' => '1000.000000',
        'status' => PurchaseBillStatus::POSTED,
    ]);
    VendorPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor1->id,
        'bank_account_id' => $bankAccount->id,
        'amount' => '300.000000',
        'status' => VendorPaymentStatus::POSTED,
    ]);

    // Vendor 2 has posted bill of RM 5000 -> balance RM 5000
    PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor2->id,
        'grand_total' => '5000.000000',
        'status' => PurchaseBillStatus::POSTED,
    ]);

    $balanceAction = app(GenerateVendorBalanceAction::class);
    $balanceVendor1 = $balanceAction->execute($vendor1, app(CurrentTenant::class));
    $balanceVendor2 = $balanceAction->execute($vendor2, app(CurrentTenant::class));

    expect($balanceVendor1['balance'])->toBe('700.000000')
        ->and($balanceVendor2['balance'])->toBe('5000.000000');
});

it('calculates vendor statement with prior opening balance and running balance', function () {
    $tenant = createPaymentTestTenant();
    $user = createPaymentAuthUser($tenant);
    $this->actingAs($user);

    $bankAccount = Account::where('tenant_id', $tenant->id)->where('code', '1130')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();
    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    // Before report period (prior transactions):
    // Prior bill: RM 800
    // Prior payment: RM 300
    // Opening balance should be: RM 500
    PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bill_date' => '2026-01-10',
        'grand_total' => '800.000000',
        'status' => PurchaseBillStatus::POSTED,
    ]);
    VendorPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bank_account_id' => $bankAccount->id,
        'payment_date' => '2026-01-25',
        'amount' => '300.000000',
        'status' => VendorPaymentStatus::POSTED,
    ]);

    // Transactions inside report period (2026-02-01 to 2026-02-28):
    // Bill: RM 1000
    // Payment: RM 600
    // Expected closing balance = 500 + 1000 - 600 = 900
    PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bill_date' => '2026-02-05',
        'grand_total' => '1000.000000',
        'status' => PurchaseBillStatus::POSTED,
    ]);
    VendorPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bank_account_id' => $bankAccount->id,
        'payment_date' => '2026-02-20',
        'amount' => '600.000000',
        'status' => VendorPaymentStatus::POSTED,
    ]);

    $statementAction = app(GenerateVendorStatementAction::class);
    $statement = $statementAction->execute(
        vendor: $vendor,
        fromDate: '2026-02-01',
        toDate: '2026-02-28',
        currentTenant: app(CurrentTenant::class),
    );

    expect($statement['opening_balance'])->toBe('500.000000')
        ->and($statement['closing_balance'])->toBe('900.000000')
        ->and($statement['entries'])->toHaveCount(2)
        ->and($statement['entries'][0]['balance'])->toBe('1500.000000')
        ->and($statement['entries'][1]['balance'])->toBe('900.000000');
});

it('generates payable aging report accurately with aggregated allocation deduction and buckets', function () {
    $tenant = createPaymentTestTenant();
    $user = createPaymentAuthUser($tenant);
    $this->actingAs($user);

    $bankAccount = Account::where('tenant_id', $tenant->id)->where('code', '1130')->first();
    $payableAccount = Account::where('tenant_id', $tenant->id)->where('code', '2110')->first();
    $vendor = Vendor::factory()->create([
        'tenant_id' => $tenant->id,
        'payable_account_id' => $payableAccount->id,
        'status' => 'active',
    ]);

    $asOfDate = '2026-03-01';

    // Bill 1: due 2026-03-15 (future due date -> current bucket)
    $billCurrent = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bill_date' => '2026-02-15',
        'due_date' => '2026-03-15',
        'grand_total' => '1000.000000',
        'status' => PurchaseBillStatus::POSTED,
    ]);

    // Bill 2: due 2026-02-15 (14 days overdue as of 2026-03-01 -> 1_30 bucket)
    // Partially paid: original RM 800, allocated RM 300 -> outstanding RM 500
    $billOverdue = PurchaseBill::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bill_date' => '2026-01-15',
        'due_date' => '2026-02-15',
        'grand_total' => '800.000000',
        'status' => PurchaseBillStatus::POSTED,
    ]);

    $payment = VendorPayment::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_id' => $vendor->id,
        'bank_account_id' => $bankAccount->id,
        'amount' => '300.000000',
        'status' => VendorPaymentStatus::POSTED,
    ]);

    VendorPaymentAllocation::factory()->create([
        'tenant_id' => $tenant->id,
        'vendor_payment_id' => $payment->id,
        'purchase_bill_id' => $billOverdue->id,
        'allocated_amount' => '300.000000',
    ]);

    $agingAction = app(GeneratePayableAgingAction::class);
    $aging = $agingAction->execute(
        currentTenant: app(CurrentTenant::class),
        asOfDate: $asOfDate,
    );

    expect($aging)->toHaveCount(2);

    $currentReportItem = collect($aging)->firstWhere('bill_id', $billCurrent->public_id);
    expect($currentReportItem['bucket'])->toBe('current')
        ->and($currentReportItem['outstanding_amount'])->toBe('1000.000000');

    $overdueReportItem = collect($aging)->firstWhere('bill_id', $billOverdue->public_id);
    expect($overdueReportItem['bucket'])->toBe('1_30')
        ->and($overdueReportItem['paid_amount'])->toBe('300.000000')
        ->and($overdueReportItem['outstanding_amount'])->toBe('500.000000');
});
