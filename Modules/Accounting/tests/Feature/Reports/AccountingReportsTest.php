<?php

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Application\Actions\Reports\GenerateAccountBalanceAction;
use Modules\Accounting\Application\Actions\Reports\GenerateAccountStatementAction;
use Modules\Accounting\Application\Actions\Reports\GenerateGeneralLedgerAction;
use Modules\Accounting\Application\Actions\Reports\GenerateTrialBalanceAction;
use Modules\Accounting\Models\Account;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

function createReportTestTenant(
    string $name = 'Report Tenant',
    string $slug = 'report-tenant',
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

it('generates a trial balance from posted journals', function () {
    $tenant = createReportTestTenant(
        'Trial Balance Tenant',
        'trial-balance-tenant',
    );

    $accounts = Account::query()
        ->where('tenant_id', $tenant->id)
        ->orderBy('code')
        ->get();

    expect($accounts)->not->toBeEmpty();

    $report = app(GenerateTrialBalanceAction::class)->execute(
        tenantId: $tenant->id,
        fromDate: CarbonImmutable::parse('2026-09-01'),
        toDate: CarbonImmutable::parse('2026-09-30'),
    );

    expect($report->totalDebit)
        ->toBe($report->totalCredit);
});

it('generates account balance for a tenant account', function () {
    $tenant = createReportTestTenant(
        'Balance Tenant',
        'balance-tenant',
    );

    $account = Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->first();

    expect($account)->not->toBeNull();

    $report = app(GenerateAccountBalanceAction::class)->execute(
        tenantId: $tenant->id,
        accountId: $account->id,
    );

    expect($report->accountId)
        ->toBe($account->id)
        ->and($report->debit)->toBeString()
        ->and($report->credit)->toBeString()
        ->and($report->balance)->toBeString();
});

it('does not expose another tenants account balance', function () {
    $tenantA = createReportTestTenant('Tenant A', 'tenant-a');
    $tenantB = createReportTestTenant('Tenant B', 'tenant-b');

    $accountB = Account::query()
        ->where('tenant_id', $tenantB->id)
        ->first();

    expect($accountB)->not->toBeNull();

    expect(fn () => app(
        GenerateAccountBalanceAction::class
    )->execute(
        tenantId: $tenantA->id,
        accountId: $accountB->id,
    ))->toThrow(RuntimeException::class);
});

it('rejects an invalid general ledger date range', function () {
    $tenant = createReportTestTenant(
        'Date Range Tenant',
        'date-range-tenant',
    );

    $account = Account::query()
        ->where('tenant_id', $tenant->id)
        ->first();

    expect($account)->not->toBeNull();

    expect(fn () => app(
        GenerateGeneralLedgerAction::class
    )->execute(
        tenantId: $tenant->id,
        accountId: $account->id,
        fromDate: CarbonImmutable::parse('2026-09-30'),
        toDate: CarbonImmutable::parse('2026-09-01'),
    ))->toThrow(RuntimeException::class);
});

it('generates an account statement using the general ledger engine', function () {
    $tenant = createReportTestTenant(
        'Statement Tenant',
        'statement-tenant',
    );

    $account = Account::query()
        ->where('tenant_id', $tenant->id)
        ->first();

    expect($account)->not->toBeNull();

    $report = app(
        GenerateAccountStatementAction::class
    )->execute(
        tenantId: $tenant->id,
        accountId: $account->id,
        fromDate: CarbonImmutable::parse('2026-09-01'),
        toDate: CarbonImmutable::parse('2026-09-30'),
    );

    expect($report->accountId)
        ->toBe($account->id)
        ->and($report->lines)
        ->toBeArray();
});
