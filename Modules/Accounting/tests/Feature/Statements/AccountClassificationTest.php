<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Application\Services\Statements\AccountClassificationService;
use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use Modules\Accounting\Models\Account;
use Modules\Tenancy\Application\Actions\CreateTenantAction;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Models\Tenant;

uses(RefreshDatabase::class);

function createClassificationTestTenant(
    string $name = 'Classification Tenant',
    string $slug = 'classification-tenant',
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

it('resolves an account financial statement section', function () {
    $tenant = createClassificationTestTenant(
        'Classification Tenant',
        'classification-tenant',
    );

    $account = Account::query()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    $section = app(
        AccountClassificationService::class
    )->section($account);

    expect($section)
        ->toBeInstanceOf(FinancialStatementSection::class);
});

it('rejects an account without financial statement classification', function () {
    $tenant = createClassificationTestTenant(
        'Unclassified Tenant',
        'unclassified-tenant',
    );

    $account = Account::query()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    $account->update(['financial_statement_section' => null]);
    $account->accountType->update(['financial_statement_section' => null]);
    $account->refresh();

    expect(fn () => app(
        AccountClassificationService::class
    )->section($account))
        ->toThrow(RuntimeException::class);
});
