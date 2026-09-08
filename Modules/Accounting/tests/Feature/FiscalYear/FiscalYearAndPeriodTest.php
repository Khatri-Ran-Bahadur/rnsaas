<?php

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Application\Actions\AccountingPeriod\CloseAccountingPeriodAction;
use Modules\Accounting\Application\Actions\AccountingPeriod\GenerateAccountingPeriodsAction;
use Modules\Accounting\Application\Actions\AccountingPeriod\ReopenAccountingPeriodAction;
use Modules\Accounting\Application\Actions\FiscalYear\CloseFiscalYearAction;
use Modules\Accounting\Application\Actions\FiscalYear\CreateFiscalYearAction;
use Modules\Accounting\Application\Actions\FiscalYear\ReopenFiscalYearAction;
use Modules\Accounting\Application\Services\AccountingPeriodResolver;
use Modules\Accounting\Domain\Enums\AccountingPeriodStatus;
use Modules\Accounting\Domain\Enums\FiscalYearStatus;
use Modules\Accounting\Models\AccountingPeriod;
use Modules\Accounting\Models\FiscalYear;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

function createFiscalYearTestTenant(
    string $name,
    string $slug,
): Tenant {
    return app(CreateTenantAction::class)->execute(
        new CreateTenantData(
            name: $name,
            slug: $slug,
            industry: 'services',
            countryCode: 'MY',
            timezone: 'Asia/Kathmandu',
            locale: 'en',
            currency: 'NPR',
            settings: [],
        ),
    );
}

function createFiscalYearForTenant(
    Tenant $tenant,
    string $code = 'FY2026',
    string $name = 'Fiscal Year 2026',
    string $startDate = '2026-01-01',
    string $endDate = '2026-12-31',
): FiscalYear {
    return app(CreateFiscalYearAction::class)->execute(
        tenant: $tenant,
        code: $code,
        name: $name,
        startDate: CarbonImmutable::parse($startDate),
        endDate: CarbonImmutable::parse($endDate),
        isCurrent: true,
    );
}

it('creates a fiscal year for a tenant', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Fiscal Test',
        'fiscal-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    expect($fiscalYear)
        ->tenant_id->toBe($tenant->id)
        ->code->toBe('FY2026')
        ->name->toBe('Fiscal Year 2026')
        ->status->toBe(FiscalYearStatus::OPEN)
        ->is_current->toBeTrue();
});

it('does not allow overlapping fiscal years for the same tenant', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Overlap Test',
        'overlap-test',
    );

    createFiscalYearForTenant($tenant);

    expect(fn () => app(CreateFiscalYearAction::class)->execute(
        tenant: $tenant,
        code: 'FY2026-2',
        name: 'Overlapping Year',
        startDate: CarbonImmutable::parse('2026-06-01'),
        endDate: CarbonImmutable::parse('2027-05-31'),
        isCurrent: false,
    ))->toThrow(
        InvalidArgumentException::class,
        'Fiscal year dates overlap',
    );
});

it('allows fiscal years with separate date ranges', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Separate Years',
        'separate-years',
    );

    createFiscalYearForTenant(
        tenant: $tenant,
        code: 'FY2026',
        name: 'Fiscal Year 2026',
        startDate: '2026-01-01',
        endDate: '2026-12-31',
    );

    $nextYear = createFiscalYearForTenant(
        tenant: $tenant,
        code: 'FY2027',
        name: 'Fiscal Year 2027',
        startDate: '2027-01-01',
        endDate: '2027-12-31',
    );

    expect($nextYear->code)->toBe('FY2027');
});

it('generates twelve monthly accounting periods for a calendar year', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Period Test',
        'period-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    expect($fiscalYear->periods()->count())
        ->toBe(12);

    $firstPeriod = $fiscalYear->periods()
        ->orderBy('period_number')
        ->first();

    $lastPeriod = $fiscalYear->periods()
        ->orderByDesc('period_number')
        ->first();

    expect($firstPeriod->period_number)
        ->toBe(1)
        ->and($firstPeriod->name)
        ->toBe('January 2026')
        ->and($firstPeriod->start_date->toDateString())
        ->toBe('2026-01-01')
        ->and($firstPeriod->end_date->toDateString())
        ->toBe('2026-01-31');

    expect($lastPeriod->period_number)
        ->toBe(12)
        ->and($lastPeriod->name)
        ->toBe('December 2026')
        ->and($lastPeriod->start_date->toDateString())
        ->toBe('2026-12-01')
        ->and($lastPeriod->end_date->toDateString())
        ->toBe('2026-12-31');
});

it('generates periods correctly for an april to march fiscal year', function (): void {
    $tenant = createFiscalYearTestTenant(
        'April Fiscal Year',
        'april-fiscal-year',
    );

    $fiscalYear = createFiscalYearForTenant(
        tenant: $tenant,
        code: 'FY2026-27',
        name: 'Fiscal Year 2026-27',
        startDate: '2026-04-01',
        endDate: '2027-03-31',
    );

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    expect($fiscalYear->periods()->count())
        ->toBe(12);

    $firstPeriod = $fiscalYear->periods()
        ->orderBy('period_number')
        ->first();

    $lastPeriod = $fiscalYear->periods()
        ->orderByDesc('period_number')
        ->first();

    expect($firstPeriod->name)
        ->toBe('April 2026')
        ->and($firstPeriod->start_date->toDateString())
        ->toBe('2026-04-01')
        ->and($firstPeriod->end_date->toDateString())
        ->toBe('2026-04-30');

    expect($lastPeriod->name)
        ->toBe('March 2027')
        ->and($lastPeriod->start_date->toDateString())
        ->toBe('2027-03-01')
        ->and($lastPeriod->end_date->toDateString())
        ->toBe('2027-03-31');
});

it('does not generate accounting periods twice', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Duplicate Period Test',
        'duplicate-period-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    $action = app(GenerateAccountingPeriodsAction::class);

    $action->execute($fiscalYear);

    expect(fn () => $action->execute($fiscalYear))
        ->toThrow(
            InvalidArgumentException::class,
            'already been generated',
        );
});

it('can close an accounting period', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Close Period Test',
        'close-period-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    $period = $fiscalYear->periods()
        ->where('period_number', 1)
        ->firstOrFail();

    $closedPeriod = app(CloseAccountingPeriodAction::class)
        ->execute($period);

    expect($closedPeriod->status)
        ->toBe(AccountingPeriodStatus::CLOSED)
        ->and($closedPeriod->closed_at)
        ->not->toBeNull();
});

it('does not allow closing an already closed period', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Double Close Test',
        'double-close-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    $period = $fiscalYear->periods()
        ->where('period_number', 1)
        ->firstOrFail();

    $action = app(CloseAccountingPeriodAction::class);

    $action->execute($period);

    expect(fn () => $action->execute($period->refresh()))
        ->toThrow(
            InvalidArgumentException::class,
            'already closed',
        );
});

it('can reopen an accounting period when fiscal year is open', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Reopen Period Test',
        'reopen-period-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    $period = $fiscalYear->periods()
        ->where('period_number', 1)
        ->firstOrFail();

    app(CloseAccountingPeriodAction::class)
        ->execute($period);

    $reopened = app(ReopenAccountingPeriodAction::class)
        ->execute($period->refresh());

    expect($reopened->status)
        ->toBe(AccountingPeriodStatus::OPEN)
        ->and($reopened->closed_at)
        ->toBeNull();
});

it('resolves an open accounting period by transaction date', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Resolver Test',
        'resolver-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    $period = app(AccountingPeriodResolver::class)
        ->resolve(
            $tenant->id,
            CarbonImmutable::parse('2026-05-15'),
        );

    expect($period->period_number)
        ->toBe(5)
        ->and($period->name)
        ->toBe('May 2026');
});

it('rejects posting into a closed accounting period', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Closed Resolver Test',
        'closed-resolver-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    $period = $fiscalYear->periods()
        ->where('period_number', 5)
        ->firstOrFail();

    app(CloseAccountingPeriodAction::class)
        ->execute($period);

    expect(fn () => app(AccountingPeriodResolver::class)->resolve(
        $tenant->id,
        CarbonImmutable::parse('2026-05-15'),
    ))->toThrow(
        InvalidArgumentException::class,
        'closed',
    );
});

it('cannot close a fiscal year while periods are open', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Close Year Test',
        'close-year-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    expect(fn () => app(CloseFiscalYearAction::class)->execute(
        $fiscalYear,
    ))->toThrow(
        InvalidArgumentException::class,
        'All accounting periods must be closed',
    );
});

it('can close a fiscal year after all periods are closed', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Complete Close Test',
        'complete-close-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    $periods = $fiscalYear->periods()
        ->orderBy('period_number')
        ->get();

    foreach ($periods as $period) {
        app(CloseAccountingPeriodAction::class)
            ->execute($period);
    }

    $closedYear = app(CloseFiscalYearAction::class)
        ->execute($fiscalYear->refresh());

    expect($closedYear->status)
        ->toBe(FiscalYearStatus::CLOSED)
        ->and($closedYear->is_current)
        ->toBeFalse()
        ->and($closedYear->closed_at)
        ->not->toBeNull();
});

it('cannot reopen a period when its fiscal year is closed', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Closed Year Reopen Test',
        'closed-year-reopen-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    $periods = $fiscalYear->periods()
        ->orderBy('period_number')
        ->get();

    foreach ($periods as $period) {
        app(CloseAccountingPeriodAction::class)
            ->execute($period);
    }

    app(CloseFiscalYearAction::class)
        ->execute($fiscalYear->refresh());

    $period = $periods->first()->refresh();

    expect(fn () => app(ReopenAccountingPeriodAction::class)
        ->execute($period))
        ->toThrow(
            InvalidArgumentException::class,
            'closed fiscal year',
        );
});

it('can reopen a closed fiscal year', function (): void {
    $tenant = createFiscalYearTestTenant(
        'Reopen Year Test',
        'reopen-year-test',
    );

    $fiscalYear = createFiscalYearForTenant($tenant);

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    $periods = $fiscalYear->periods()
        ->orderBy('period_number')
        ->get();

    foreach ($periods as $period) {
        app(CloseAccountingPeriodAction::class)
            ->execute($period);
    }

    app(CloseFiscalYearAction::class)
        ->execute($fiscalYear->refresh());

    $reopened = app(ReopenFiscalYearAction::class)
        ->execute($fiscalYear->refresh());

    expect($reopened->status)
        ->toBe(FiscalYearStatus::OPEN)
        ->and($reopened->closed_at)
        ->toBeNull();
});

it('keeps fiscal years isolated between tenants', function (): void {
    $firstTenant = createFiscalYearTestTenant(
        'First Fiscal Tenant',
        'first-fiscal-tenant',
    );

    $secondTenant = createFiscalYearTestTenant(
        'Second Fiscal Tenant',
        'second-fiscal-tenant',
    );

    createFiscalYearForTenant(
        tenant: $firstTenant,
        code: 'FY2026',
    );

    createFiscalYearForTenant(
        tenant: $secondTenant,
        code: 'FY2026',
    );

    expect(FiscalYear::where('tenant_id', $firstTenant->id)->count())
        ->toBe(1);

    expect(FiscalYear::where('tenant_id', $secondTenant->id)->count())
        ->toBe(1);

    expect(
        AccountingPeriod::where('tenant_id', $firstTenant->id)->count(),
    )->toBe(0);
});
