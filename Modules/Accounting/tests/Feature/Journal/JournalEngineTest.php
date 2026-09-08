<?php

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Application\Actions\AccountingPeriod\CloseAccountingPeriodAction;
use Modules\Accounting\Application\Actions\AccountingPeriod\GenerateAccountingPeriodsAction;
use Modules\Accounting\Application\Actions\FiscalYear\CreateFiscalYearAction;
use Modules\Accounting\Application\Actions\Journal\CreateJournalEntryAction;
use Modules\Accounting\Application\Actions\Journal\PostJournalEntryAction;
use Modules\Accounting\Application\Actions\Journal\ReverseJournalEntryAction;
use Modules\Accounting\Domain\Enums\JournalEntryStatus;
use Modules\Accounting\Domain\Enums\JournalLineType;
use Modules\Accounting\Domain\Exceptions\JournalPostingException;
use Modules\Accounting\Domain\Exceptions\UnbalancedJournalException;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\JournalEntry;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

function createJournalTestTenant(
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

function createJournalTestFiscalYear(
    Tenant $tenant,
): FiscalYear {
    $fiscalYear = app(CreateFiscalYearAction::class)->execute(
        tenant: $tenant,
        code: 'FY2026',
        name: 'Fiscal Year 2026',
        startDate: CarbonImmutable::parse('2026-01-01'),
        endDate: CarbonImmutable::parse('2026-12-31'),
        isCurrent: true,
    );

    app(GenerateAccountingPeriodsAction::class)
        ->execute($fiscalYear);

    return $fiscalYear;
}

function accountForJournal(
    Tenant $tenant,
    string $code,
): Account {
    return Account::query()
        ->where('tenant_id', $tenant->id)
        ->where('code', $code)
        ->firstOrFail();
}

it('creates a balanced draft journal entry', function (): void {
    $tenant = createJournalTestTenant(
        'Journal Test',
        'journal-test',
    );

    createJournalTestFiscalYear($tenant);

    $cash = accountForJournal($tenant, '1110');
    $sales = accountForJournal($tenant, '4100');

    $journal = app(CreateJournalEntryAction::class)->execute(
        tenantId: $tenant->id,
        entryNumber: 'JE-000001',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Cash sale',
        lines: [
            [
                'account_id' => $cash->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100.000000',
            ],
            [
                'account_id' => $sales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '100.000000',
            ],
        ],
    );

    expect($journal->status)
        ->toBe(JournalEntryStatus::DRAFT)
        ->and($journal->lines)->toHaveCount(2);
});

it('rejects an unbalanced journal', function (): void {
    $tenant = createJournalTestTenant(
        'Unbalanced Test',
        'unbalanced-test',
    );

    createJournalTestFiscalYear($tenant);

    $cash = accountForJournal($tenant, '1110');
    $sales = accountForJournal($tenant, '4100');

    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        tenantId: $tenant->id,
        entryNumber: 'JE-UNBALANCED',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Invalid journal',
        lines: [
            [
                'account_id' => $cash->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100',
            ],
            [
                'account_id' => $sales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '90',
            ],
        ],
    ))->toThrow(UnbalancedJournalException::class);
});

it('does not allow a header account in a journal', function (): void {
    $tenant = createJournalTestTenant(
        'Header Journal Test',
        'header-journal-test',
    );

    createJournalTestFiscalYear($tenant);

    $header = accountForJournal($tenant, '1000');
    $sales = accountForJournal($tenant, '4100');

    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        tenantId: $tenant->id,
        entryNumber: 'JE-HEADER',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Header account test',
        lines: [
            [
                'account_id' => $header->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100',
            ],
            [
                'account_id' => $sales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '100',
            ],
        ],
    ))->toThrow(
        InvalidArgumentException::class,
        'not postable',
    );
});

it('posts a draft journal entry', function (): void {
    $tenant = createJournalTestTenant(
        'Posting Test',
        'posting-test',
    );

    createJournalTestFiscalYear($tenant);

    $cash = accountForJournal($tenant, '1110');
    $sales = accountForJournal($tenant, '4100');

    $journal = app(CreateJournalEntryAction::class)->execute(
        tenantId: $tenant->id,
        entryNumber: 'JE-POST-001',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Cash sale',
        lines: [
            [
                'account_id' => $cash->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100',
            ],
            [
                'account_id' => $sales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '100',
            ],
        ],
    );

    $posted = app(PostJournalEntryAction::class)
        ->execute($journal);

    expect($posted->status)
        ->toBe(JournalEntryStatus::POSTED)
        ->and($posted->posted_at)
        ->not->toBeNull();
});

it('does not allow a posted journal to be posted again', function (): void {
    $tenant = createJournalTestTenant(
        'Double Post Test',
        'double-post-test',
    );

    createJournalTestFiscalYear($tenant);

    $cash = accountForJournal($tenant, '1110');
    $sales = accountForJournal($tenant, '4100');

    $journal = app(CreateJournalEntryAction::class)->execute(
        tenantId: $tenant->id,
        entryNumber: 'JE-DOUBLE-POST',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Cash sale',
        lines: [
            [
                'account_id' => $cash->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100',
            ],
            [
                'account_id' => $sales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '100',
            ],
        ],
    );

    $action = app(PostJournalEntryAction::class);

    $action->execute($journal);

    expect(fn () => $action->execute($journal->refresh()))
        ->toThrow(
            JournalPostingException::class,
            'Only draft',
        );
});

it('prevents posting into a closed accounting period', function (): void {
    $tenant = createJournalTestTenant(
        'Closed Period Journal',
        'closed-period-journal',
    );

    $fiscalYear = createJournalTestFiscalYear($tenant);

    $period = $fiscalYear->periods()
        ->where('period_number', 1)
        ->firstOrFail();

    app(
        CloseAccountingPeriodAction::class,
    )->execute($period);

    $cash = accountForJournal($tenant, '1110');
    $sales = accountForJournal($tenant, '4100');

    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        tenantId: $tenant->id,
        entryNumber: 'JE-CLOSED',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Closed period',
        lines: [
            [
                'account_id' => $cash->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100',
            ],
            [
                'account_id' => $sales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '100',
            ],
        ],
    ))->toThrow(
        InvalidArgumentException::class,
        'closed',
    );
});

it('prevents an account from another tenant being used', function (): void {
    $firstTenant = createJournalTestTenant(
        'First Journal Tenant',
        'first-journal-tenant',
    );

    $secondTenant = createJournalTestTenant(
        'Second Journal Tenant',
        'second-journal-tenant',
    );

    createJournalTestFiscalYear($firstTenant);
    createJournalTestFiscalYear($secondTenant);

    $firstCash = accountForJournal($firstTenant, '1110');
    $secondSales = accountForJournal($secondTenant, '4100');

    expect(fn () => app(CreateJournalEntryAction::class)->execute(
        tenantId: $firstTenant->id,
        entryNumber: 'JE-CROSS-TENANT',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Cross tenant attempt',
        lines: [
            [
                'account_id' => $firstCash->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100',
            ],
            [
                'account_id' => $secondSales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '100',
            ],
        ],
    ))->toThrow(
        InvalidArgumentException::class,
        'do not belong',
    );
});

it('returns the existing journal for the same idempotency key', function (): void {
    $tenant = createJournalTestTenant(
        'Idempotency Test',
        'idempotency-test',
    );

    createJournalTestFiscalYear($tenant);

    $cash = accountForJournal($tenant, '1110');
    $sales = accountForJournal($tenant, '4100');

    $action = app(CreateJournalEntryAction::class);

    $first = $action->execute(
        tenantId: $tenant->id,
        entryNumber: 'JE-IDEMPOTENT-001',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Idempotent sale',
        lines: [
            [
                'account_id' => $cash->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100',
            ],
            [
                'account_id' => $sales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '100',
            ],
        ],
        idempotencyKey: 'SALE-10001',
    );

    $second = $action->execute(
        tenantId: $tenant->id,
        entryNumber: 'JE-ANOTHER-NUMBER',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Duplicate request',
        lines: [
            [
                'account_id' => $cash->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100',
            ],
            [
                'account_id' => $sales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '100',
            ],
        ],
        idempotencyKey: 'SALE-10001',
    );

    expect($second->id)
        ->toBe($first->id);

    expect(JournalEntry::where(
        'tenant_id',
        $tenant->id,
    )->count())->toBe(1);
});

it('reverses a posted journal by creating an opposite entry', function (): void {
    $tenant = createJournalTestTenant(
        'Reversal Test',
        'reversal-test',
    );

    createJournalTestFiscalYear($tenant);

    $cash = accountForJournal($tenant, '1110');
    $sales = accountForJournal($tenant, '4100');

    $journal = app(CreateJournalEntryAction::class)->execute(
        tenantId: $tenant->id,
        entryNumber: 'JE-REV-001',
        entryDate: CarbonImmutable::parse('2026-01-15'),
        description: 'Original sale',
        lines: [
            [
                'account_id' => $cash->id,
                'line_type' => JournalLineType::DEBIT,
                'amount' => '100',
            ],
            [
                'account_id' => $sales->id,
                'line_type' => JournalLineType::CREDIT,
                'amount' => '100',
            ],
        ],
    );

    app(PostJournalEntryAction::class)
        ->execute($journal);

    $reversal = app(ReverseJournalEntryAction::class)
        ->execute(
            $journal->refresh(),
            CarbonImmutable::parse('2026-01-20'),
        );

    expect($journal->refresh()->status)
        ->toBe(JournalEntryStatus::REVERSED);

    expect($reversal->status)
        ->toBe(JournalEntryStatus::POSTED);

    expect($reversal->lines)->toHaveCount(2);

    $reversalDebit = $reversal->lines
        ->firstWhere(
            'account_id',
            $sales->id,
        );

    $reversalCredit = $reversal->lines
        ->firstWhere(
            'account_id',
            $cash->id,
        );

    expect($reversalDebit->line_type)
        ->toBe(JournalLineType::DEBIT);

    expect($reversalCredit->line_type)
        ->toBe(JournalLineType::CREDIT);
});
