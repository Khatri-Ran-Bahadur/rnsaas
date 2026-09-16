<?php

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Accounting\Application\Actions\AccountingPeriod\GenerateAccountingPeriodsAction;
use Modules\Accounting\Application\Actions\FiscalYear\CreateFiscalYearAction;
use Modules\Accounting\Domain\Enums\JournalEntryStatus;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

uses(RefreshDatabase::class);

function makeJournalEntryUiTenant(): Tenant
{
    return app(CreateTenantAction::class)->execute(new CreateTenantData(
        name: 'Journal Entry UI Org', slug: 'journal-entry-ui-'.uniqid(),
        industry: 'services', status: TenantStatus::Active, countryCode: 'MY',
        timezone: 'Asia/Kuala_Lumpur', locale: 'en', currency: 'MYR', settings: [],
    ));
}

function journalEntryUiUser(Tenant $tenant): User
{
    $user = User::factory()->create();
    TenantMembership::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'status' => 'active']);
    session(['current_tenant_id' => $tenant->id]);
    app(CurrentTenant::class)->set($tenant);

    return $user;
}

function journalEntryUiFiscalYear(Tenant $tenant): void
{
    $fiscalYear = app(CreateFiscalYearAction::class)->execute(
        tenant: $tenant, code: 'FY2026', name: 'Fiscal Year 2026',
        startDate: CarbonImmutable::parse('2026-01-01'), endDate: CarbonImmutable::parse('2026-12-31'),
        isCurrent: true,
    );
    app(GenerateAccountingPeriodsAction::class)->execute($fiscalYear);
}

it('renders manual journal pages for the current tenant', function (): void {
    $tenant = makeJournalEntryUiTenant();
    $user = journalEntryUiUser($tenant);

    $this->actingAs($user)->get('/admin/accounting/journal-entries')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Accounting/JournalEntries/Index')->has('journals'));
    $this->actingAs($user)->get('/admin/accounting/journal-entries/create')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Accounting/JournalEntries/Create')->has('accounts'));
});

it('stores a balanced journal entry as a draft', function (): void {
    $tenant = makeJournalEntryUiTenant();
    $user = journalEntryUiUser($tenant);
    journalEntryUiFiscalYear($tenant);
    $cash = Account::query()->where('tenant_id', $tenant->id)->where('code', '1110')->firstOrFail();
    $sales = Account::query()->where('tenant_id', $tenant->id)->where('code', '4100')->firstOrFail();

    $this->actingAs($user)->post('/admin/accounting/journal-entries', [
        'entry_number' => 'JE-UI-001', 'entry_date' => '2026-01-15', 'description' => 'Cash sale adjustment',
        'lines' => [
            ['account_id' => $cash->id, 'line_type' => 'debit', 'amount' => '100.00'],
            ['account_id' => $sales->id, 'line_type' => 'credit', 'amount' => '100.00'],
        ],
    ])->assertRedirect('/admin/accounting/journal-entries');

    $journal = JournalEntry::query()->where('entry_number', 'JE-UI-001')->firstOrFail();
    expect($journal->tenant_id)->toBe($tenant->id)->and($journal->status)->toBe(JournalEntryStatus::DRAFT)->and($journal->lines)->toHaveCount(2);
});

it('rejects an unbalanced journal entry before it is stored', function (): void {
    $tenant = makeJournalEntryUiTenant();
    $user = journalEntryUiUser($tenant);
    journalEntryUiFiscalYear($tenant);
    $cash = Account::query()->where('tenant_id', $tenant->id)->where('code', '1110')->firstOrFail();
    $sales = Account::query()->where('tenant_id', $tenant->id)->where('code', '4100')->firstOrFail();

    $this->actingAs($user)->from('/admin/accounting/journal-entries/create')->post('/admin/accounting/journal-entries', [
        'entry_number' => 'JE-UI-UNBALANCED', 'entry_date' => '2026-01-15', 'description' => 'Invalid adjustment',
        'lines' => [
            ['account_id' => $cash->id, 'line_type' => 'debit', 'amount' => '100.00'],
            ['account_id' => $sales->id, 'line_type' => 'credit', 'amount' => '90.00'],
        ],
    ])->assertRedirect('/admin/accounting/journal-entries/create')->assertSessionHasErrors('lines');

    expect(JournalEntry::query()->where('entry_number', 'JE-UI-UNBALANCED')->exists())->toBeFalse();
});
