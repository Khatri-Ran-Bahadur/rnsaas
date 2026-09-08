<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Application\Actions\Accounting\ProvisionChartOfAccountsAction;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AccountGroup;
use Modules\Accounting\Models\AccountType;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

function createAccountingTestTenant(
    string $name,
    string $slug,
): Tenant {
    return app(CreateTenantAction::class)->execute(
        new CreateTenantData(
            name: $name,
            slug: $slug,
            industry: 'services',
            countryCode: 'MY',
            timezone: 'Asia/Kuala_Lumpur',
            locale: 'en',
            currency: 'MYR',
            settings: [],
        ),
    );
}

it('provisions a complete default chart of accounts when an organization is created', function (): void {
    $tenant = createAccountingTestTenant(
        'Test Organization',
        'test-organization',
    );

    expect(AccountType::where('tenant_id', $tenant->id)->count())
        ->toBe(8);

    expect(AccountGroup::where('tenant_id', $tenant->id)->count())
        ->toBe(15);

    expect(Account::where('tenant_id', $tenant->id)->count())
        ->toBeGreaterThan(20);
});

it('creates system account types with correct accounting behaviour', function (): void {
    $tenant = createAccountingTestTenant(
        'Accounting Test',
        'accounting-test',
    );

    $assetType = AccountType::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', 'ASSET')
        ->first();

    expect($assetType)->not->toBeNull()
        ->and($assetType->is_system)->toBeTrue()
        ->and($assetType->classification->value)->toBe('asset')
        ->and($assetType->normal_balance->value)->toBe('debit')
        ->and($assetType->financial_statement)->toBe('balance_sheet');
});

it('creates revenue accounts with credit normal balance', function (): void {
    $tenant = createAccountingTestTenant(
        'Revenue Test',
        'revenue-test',
    );

    $revenueType = AccountType::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', 'REVENUE')
        ->first();

    expect($revenueType)->not->toBeNull()
        ->and($revenueType->normal_balance->value)->toBe('credit')
        ->and($revenueType->financial_statement)->toBe('income_statement');

    $salesRevenue = Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', '4100')
        ->first();

    expect($salesRevenue)->not->toBeNull()
        ->and($salesRevenue->is_postable)->toBeTrue();
});

it('creates header accounts that are not postable', function (): void {
    $tenant = createAccountingTestTenant(
        'Header Test',
        'header-test',
    );

    $assets = Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', '1000')
        ->first();

    expect($assets)->not->toBeNull()
        ->and($assets->is_postable)->toBeFalse()
        ->and($assets->is_system)->toBeTrue();
});

it('creates posting accounts under the correct parent', function (): void {
    $tenant = createAccountingTestTenant(
        'Hierarchy Test',
        'hierarchy-test',
    );

    $cashGroup = Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', '1100')
        ->first();

    $cash = Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', '1110')
        ->first();

    expect($cashGroup)->not->toBeNull()
        ->and($cash)->not->toBeNull()
        ->and($cashGroup->is_postable)->toBeFalse()
        ->and($cash->is_postable)->toBeTrue()
        ->and($cash->parent_id)->toBe($cashGroup->id);
});

it('creates accounts completely isolated per tenant', function (): void {
    $firstTenant = createAccountingTestTenant(
        'First',
        'first',
    );

    $secondTenant = createAccountingTestTenant(
        'Second',
        'second',
    );

    expect(AccountType::where('tenant_id', $firstTenant->id)->count())
        ->toBe(8);

    expect(AccountType::where('tenant_id', $secondTenant->id)->count())
        ->toBe(8);

    expect(AccountGroup::where('tenant_id', $firstTenant->id)->count())
        ->toBe(15);

    expect(AccountGroup::where('tenant_id', $secondTenant->id)->count())
        ->toBe(15);

    expect(Account::where('tenant_id', $firstTenant->id)->count())
        ->toBeGreaterThan(20);

    expect(Account::where('tenant_id', $secondTenant->id)->count())
        ->toBeGreaterThan(20);

    $firstCash = Account::query()
        ->where('tenant_id', $firstTenant->id)
        ->where('code', '1110')
        ->first();

    $secondCash = Account::query()
        ->where('tenant_id', $secondTenant->id)
        ->where('code', '1110')
        ->first();

    expect($firstCash)->not->toBeNull()
        ->and($secondCash)->not->toBeNull()
        ->and($firstCash->id)->not->toBe($secondCash->id)
        ->and($firstCash->tenant_id)->toBe($firstTenant->id)
        ->and($secondCash->tenant_id)->toBe($secondTenant->id);
});

it('marks control accounts correctly', function (): void {
    $tenant = createAccountingTestTenant(
        'Control Account Test',
        'control-account-test',
    );

    $receivable = Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', '1200')
        ->first();

    $inventory = Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', '1300')
        ->first();

    $payable = Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', '2110')
        ->first();

    $taxPayable = Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', '2120')
        ->first();

    expect($receivable)->not->toBeNull()
        ->and($receivable->is_control_account)->toBeTrue();

    expect($inventory)->not->toBeNull()
        ->and($inventory->is_control_account)->toBeTrue();

    expect($payable)->not->toBeNull()
        ->and($payable->is_control_account)->toBeTrue();

    expect($taxPayable)->not->toBeNull()
        ->and($taxPayable->is_control_account)->toBeTrue();
});

it('does not provision duplicate chart of accounts for the same tenant', function (): void {
    $tenant = createAccountingTestTenant(
        'Duplicate Test',
        'duplicate-test',
    );

    $accountTypeCountBefore = AccountType::where(
        'tenant_id',
        $tenant->id,
    )->count();

    $accountGroupCountBefore = AccountGroup::where(
        'tenant_id',
        $tenant->id,
    )->count();

    $accountCountBefore = Account::where(
        'tenant_id',
        $tenant->id,
    )->count();

    expect($accountTypeCountBefore)->toBe(8)
        ->and($accountGroupCountBefore)->toBe(15)
        ->and($accountCountBefore)->toBeGreaterThan(20);

    /*
     * Provisioning should be idempotent.
     *
     * We intentionally call the provisioning action again
     * after the tenant has already been created.
     */
    app(
        ProvisionChartOfAccountsAction::class
    )->execute($tenant);

    expect(AccountType::where('tenant_id', $tenant->id)->count())
        ->toBe($accountTypeCountBefore);

    expect(AccountGroup::where('tenant_id', $tenant->id)->count())
        ->toBe($accountGroupCountBefore);

    expect(Account::where('tenant_id', $tenant->id)->count())
        ->toBe($accountCountBefore);
});
