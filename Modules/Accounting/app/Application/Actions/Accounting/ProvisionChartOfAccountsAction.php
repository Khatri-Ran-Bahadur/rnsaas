<?php

namespace Modules\Accounting\Application\Actions\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Accounting\Domain\Enums\AccountClassification;
use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AccountGroup;
use Modules\Accounting\Models\AccountType;
use Modules\Tenancy\Models\Tenant;

final class ProvisionChartOfAccountsAction
{
    public function execute(Tenant $tenant): void
    {
        if (AccountType::query()->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($tenant): void {
            $types = $this->createTypes($tenant);

            $groups = $this->createGroups(
                $tenant,
                $types,
            );

            $this->createAccounts(
                $tenant,
                $groups,
                $types,
            );
        });
    }

    /**
     * @return array<string, AccountType>
     */
    private function createTypes(Tenant $tenant): array
    {
        $definitions = [
            [
                'code' => 'ASSET',
                'name' => 'Assets',
                'classification' => AccountClassification::ASSET,
                'section' => FinancialStatementSection::ASSETS,
                'sort_order' => 10,
            ],
            [
                'code' => 'LIABILITY',
                'name' => 'Liabilities',
                'classification' => AccountClassification::LIABILITY,
                'section' => FinancialStatementSection::LIABILITIES,
                'sort_order' => 20,
            ],
            [
                'code' => 'EQUITY',
                'name' => 'Equity',
                'classification' => AccountClassification::EQUITY,
                'section' => FinancialStatementSection::EQUITY,
                'sort_order' => 30,
            ],
            [
                'code' => 'REVENUE',
                'name' => 'Revenue',
                'classification' => AccountClassification::REVENUE,
                'section' => FinancialStatementSection::REVENUE,
                'sort_order' => 40,
            ],
            [
                'code' => 'COST_OF_SALES',
                'name' => 'Cost of Sales',
                'classification' => AccountClassification::COST_OF_SALES,
                'section' => FinancialStatementSection::COST_OF_SALES,
                'sort_order' => 50,
            ],
            [
                'code' => 'EXPENSE',
                'name' => 'Expenses',
                'classification' => AccountClassification::EXPENSE,
                'section' => FinancialStatementSection::OPERATING_EXPENSES,
                'sort_order' => 60,
            ],
            [
                'code' => 'OTHER_INCOME',
                'name' => 'Other Income',
                'classification' => AccountClassification::OTHER_INCOME,
                'section' => FinancialStatementSection::OTHER_INCOME,
                'sort_order' => 70,
            ],
            [
                'code' => 'OTHER_EXPENSE',
                'name' => 'Other Expenses',
                'classification' => AccountClassification::OTHER_EXPENSE,
                'section' => FinancialStatementSection::OTHER_EXPENSES,
                'sort_order' => 80,
            ],
        ];

        $result = [];

        foreach ($definitions as $definition) {
            $classification = $definition['classification'];

            $result[$definition['code']] = AccountType::query()->create([
                'tenant_id' => $tenant->id,
                'public_id' => (string) Str::uuid(),
                'code' => $definition['code'],
                'name' => $definition['name'],
                'classification' => $classification,
                'normal_balance' => $classification->defaultNormalBalance(),
                'financial_statement' => $classification->financialStatement(),
                'financial_statement_section' => $definition['section'],
                'is_system' => true,
                'is_active' => true,
                'sort_order' => $definition['sort_order'],
            ]);
        }

        return $result;
    }

    /**
     * @param  array<string, AccountType>  $types
     * @return array<string, AccountGroup>
     */
    private function createGroups(
        Tenant $tenant,
        array $types,
    ): array {
        $definitions = [
            ['code' => 'CURRENT_ASSETS', 'name' => 'Current Assets', 'type' => 'ASSET', 'sort' => 10],
            ['code' => 'OTHER_ASSETS', 'name' => 'Other Assets', 'type' => 'ASSET', 'sort' => 20],
            ['code' => 'FIXED_ASSETS', 'name' => 'Fixed Assets', 'type' => 'ASSET', 'sort' => 30],

            ['code' => 'CURRENT_LIABILITIES', 'name' => 'Current Liabilities', 'type' => 'LIABILITY', 'sort' => 10],
            ['code' => 'LONG_TERM_LIABILITIES', 'name' => 'Long-term Liabilities', 'type' => 'LIABILITY', 'sort' => 20],
            ['code' => 'OTHER_LIABILITIES', 'name' => 'Other Liabilities', 'type' => 'LIABILITY', 'sort' => 30],

            ['code' => 'EQUITY_CAPITAL', 'name' => 'Share Capital', 'type' => 'EQUITY', 'sort' => 10],
            ['code' => 'RETAINED_EARNINGS', 'name' => 'Retained Earnings', 'type' => 'EQUITY', 'sort' => 20],

            ['code' => 'SALES_REVENUE', 'name' => 'Sales Revenue', 'type' => 'REVENUE', 'sort' => 10],
            ['code' => 'SERVICE_REVENUE', 'name' => 'Service Revenue', 'type' => 'REVENUE', 'sort' => 20],

            ['code' => 'COST_OF_GOODS_SOLD', 'name' => 'Cost of Goods Sold', 'type' => 'COST_OF_SALES', 'sort' => 10],

            ['code' => 'OPERATING_EXPENSES', 'name' => 'Operating Expenses', 'type' => 'EXPENSE', 'sort' => 10],
            ['code' => 'ADMINISTRATIVE_EXPENSES', 'name' => 'Administrative Expenses', 'type' => 'EXPENSE', 'sort' => 20],

            ['code' => 'OTHER_INCOME', 'name' => 'Other Income', 'type' => 'OTHER_INCOME', 'sort' => 10],
            ['code' => 'OTHER_EXPENSES', 'name' => 'Other Expenses', 'type' => 'OTHER_EXPENSE', 'sort' => 10],
        ];

        $result = [];

        foreach ($definitions as $definition) {
            $result[$definition['code']] = AccountGroup::query()->create([
                'tenant_id' => $tenant->id,
                'public_id' => (string) Str::uuid(),
                'account_type_id' => $types[$definition['type']]->id,
                'code' => $definition['code'],
                'name' => $definition['name'],
                'is_system' => true,
                'is_active' => true,
                'sort_order' => $definition['sort'],
            ]);
        }

        return $result;
    }

    /**
     * @param  array<string, AccountGroup>  $groups
     * @param  array<string, AccountType>  $types
     */
    private function createAccounts(
        Tenant $tenant,
        array $groups,
        array $types,
    ): void {
        $accounts = [
            [
                'code' => '1000',
                'name' => 'Assets',
                'type' => 'ASSET',
                'group' => 'CURRENT_ASSETS',
                'postable' => false,
                'control' => false,
            ],
            [
                'code' => '1100',
                'name' => 'Cash and Cash Equivalents',
                'type' => 'ASSET',
                'group' => 'CURRENT_ASSETS',
                'postable' => false,
                'control' => false,
                'parent' => '1000',
            ],
            [
                'code' => '1110',
                'name' => 'Cash',
                'type' => 'ASSET',
                'group' => 'CURRENT_ASSETS',
                'postable' => true,
                'control' => false,
                'parent' => '1100',
            ],
            [
                'code' => '1120',
                'name' => 'Petty Cash',
                'type' => 'ASSET',
                'group' => 'CURRENT_ASSETS',
                'postable' => true,
                'control' => false,
                'parent' => '1100',
            ],
            [
                'code' => '1130',
                'name' => 'Bank Account - Main',
                'type' => 'ASSET',
                'group' => 'CURRENT_ASSETS',
                'postable' => true,
                'control' => false,
                'parent' => '1100',
            ],
            [
                'code' => '1200',
                'name' => 'Accounts Receivable',
                'type' => 'ASSET',
                'group' => 'CURRENT_ASSETS',
                'postable' => true,
                'control' => true,
            ],
            [
                'code' => '1300',
                'name' => 'Inventory',
                'type' => 'ASSET',
                'group' => 'CURRENT_ASSETS',
                'postable' => true,
                'control' => true,
            ],
            [
                'code' => '1500',
                'name' => 'Fixed Assets',
                'type' => 'ASSET',
                'group' => 'FIXED_ASSETS',
                'postable' => false,
                'control' => false,
            ],
            [
                'code' => '1510',
                'name' => 'Equipment',
                'type' => 'ASSET',
                'group' => 'FIXED_ASSETS',
                'postable' => true,
                'control' => false,
                'parent' => '1500',
            ],
            [
                'code' => '1590',
                'name' => 'Accumulated Depreciation - Equipment',
                'type' => 'ASSET',
                'group' => 'FIXED_ASSETS',
                'postable' => true,
                'control' => false,
                'parent' => '1500',
            ],

            [
                'code' => '2000',
                'name' => 'Liabilities',
                'type' => 'LIABILITY',
                'group' => 'CURRENT_LIABILITIES',
                'postable' => false,
                'control' => false,
            ],
            [
                'code' => '2100',
                'name' => 'Current Liabilities',
                'type' => 'LIABILITY',
                'group' => 'CURRENT_LIABILITIES',
                'postable' => false,
                'control' => false,
                'parent' => '2000',
            ],
            [
                'code' => '2110',
                'name' => 'Accounts Payable',
                'type' => 'LIABILITY',
                'group' => 'CURRENT_LIABILITIES',
                'postable' => true,
                'control' => true,
                'parent' => '2100',
            ],
            [
                'code' => '2120',
                'name' => 'Tax Payable',
                'type' => 'LIABILITY',
                'group' => 'CURRENT_LIABILITIES',
                'postable' => true,
                'control' => true,
                'parent' => '2100',
            ],

            [
                'code' => '3000',
                'name' => 'Equity',
                'type' => 'EQUITY',
                'group' => 'EQUITY_CAPITAL',
                'postable' => false,
                'control' => false,
            ],
            [
                'code' => '3100',
                'name' => 'Share Capital',
                'type' => 'EQUITY',
                'group' => 'EQUITY_CAPITAL',
                'postable' => true,
                'control' => false,
                'parent' => '3000',
            ],
            [
                'code' => '3200',
                'name' => 'Retained Earnings',
                'type' => 'EQUITY',
                'group' => 'RETAINED_EARNINGS',
                'postable' => true,
                'control' => true,
            ],

            [
                'code' => '4000',
                'name' => 'Revenue',
                'type' => 'REVENUE',
                'group' => 'SALES_REVENUE',
                'postable' => false,
                'control' => false,
            ],
            [
                'code' => '4100',
                'name' => 'Sales Revenue',
                'type' => 'REVENUE',
                'group' => 'SALES_REVENUE',
                'postable' => true,
                'control' => false,
                'parent' => '4000',
            ],
            [
                'code' => '4200',
                'name' => 'Service Revenue',
                'type' => 'REVENUE',
                'group' => 'SERVICE_REVENUE',
                'postable' => true,
                'control' => false,
                'parent' => '4000',
            ],

            [
                'code' => '5000',
                'name' => 'Cost of Sales',
                'type' => 'COST_OF_SALES',
                'group' => 'COST_OF_GOODS_SOLD',
                'postable' => false,
                'control' => false,
            ],
            [
                'code' => '5100',
                'name' => 'Cost of Goods Sold',
                'type' => 'COST_OF_SALES',
                'group' => 'COST_OF_GOODS_SOLD',
                'postable' => true,
                'control' => false,
                'parent' => '5000',
            ],

            [
                'code' => '6000',
                'name' => 'Operating Expenses',
                'type' => 'EXPENSE',
                'group' => 'OPERATING_EXPENSES',
                'postable' => false,
                'control' => false,
            ],
            [
                'code' => '6110',
                'name' => 'Salaries Expense',
                'type' => 'EXPENSE',
                'group' => 'OPERATING_EXPENSES',
                'postable' => true,
                'control' => false,
                'parent' => '6000',
            ],
            [
                'code' => '6120',
                'name' => 'Rent Expense',
                'type' => 'EXPENSE',
                'group' => 'OPERATING_EXPENSES',
                'postable' => true,
                'control' => false,
                'parent' => '6000',
            ],
            [
                'code' => '6130',
                'name' => 'Utilities Expense',
                'type' => 'EXPENSE',
                'group' => 'OPERATING_EXPENSES',
                'postable' => true,
                'control' => false,
                'parent' => '6000',
            ],
            [
                'code' => '6140',
                'name' => 'Office Supplies',
                'type' => 'EXPENSE',
                'group' => 'OPERATING_EXPENSES',
                'postable' => true,
                'control' => false,
                'parent' => '6000',
            ],

            [
                'code' => '7000',
                'name' => 'Other Income',
                'type' => 'OTHER_INCOME',
                'group' => 'OTHER_INCOME',
                'postable' => false,
                'control' => false,
            ],
            [
                'code' => '7100',
                'name' => 'Other Income',
                'type' => 'OTHER_INCOME',
                'group' => 'OTHER_INCOME',
                'postable' => true,
                'control' => false,
                'parent' => '7000',
            ],

            [
                'code' => '8000',
                'name' => 'Other Expenses',
                'type' => 'OTHER_EXPENSE',
                'group' => 'OTHER_EXPENSES',
                'postable' => false,
                'control' => false,
            ],
            [
                'code' => '8110',
                'name' => 'Interest Expense',
                'type' => 'OTHER_EXPENSE',
                'group' => 'OTHER_EXPENSES',
                'postable' => true,
                'control' => false,
                'parent' => '8000',
            ],
            [
                'code' => '8120',
                'name' => 'Bank Charges',
                'type' => 'OTHER_EXPENSE',
                'group' => 'OTHER_EXPENSES',
                'postable' => true,
                'control' => false,
                'parent' => '8000',
            ],
        ];

        $created = [];

        foreach ($accounts as $definition) {
            $parentId = isset($definition['parent'])
                ? $created[$definition['parent']]->id
                : null;

            $created[$definition['code']] = Account::query()->create([
                'tenant_id' => $tenant->id,
                'public_id' => (string) Str::uuid(),
                'account_type_id' => $types[$definition['type']]->id,
                'account_group_id' => $groups[$definition['group']]->id,
                'parent_id' => $parentId,
                'code' => $definition['code'],
                'name' => $definition['name'],
                'is_postable' => $definition['postable'],
                'is_control_account' => $definition['control'],
                'is_system' => true,
                'is_active' => true,
            ]);
        }
    }
}
