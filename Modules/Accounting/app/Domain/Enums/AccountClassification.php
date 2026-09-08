<?php

namespace Modules\Accounting\Domain\Enums;

enum AccountClassification: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case COST_OF_SALES = 'cost_of_sales';
    case EXPENSE = 'expense';
    case OTHER_INCOME = 'other_income';
    case OTHER_EXPENSE = 'other_expense';

    public function label(): string
    {
        return match ($this) {
            self::ASSET => 'Asset',
            self::LIABILITY => 'Liability',
            self::EQUITY => 'Equity',
            self::REVENUE => 'Revenue',
            self::COST_OF_SALES => 'Cost of Sales',
            self::EXPENSE => 'Expense',
            self::OTHER_INCOME => 'Other Income',
            self::OTHER_EXPENSE => 'Other Expense',
        };
    }

    public function defaultNormalBalance(): AccountNormalBalance
    {
        return match ($this) {
            self::ASSET,
            self::COST_OF_SALES,
            self::EXPENSE,
            self::OTHER_EXPENSE => AccountNormalBalance::DEBIT,

            self::LIABILITY,
            self::EQUITY,
            self::REVENUE,
            self::OTHER_INCOME => AccountNormalBalance::CREDIT,
        };
    }

    public function financialStatement(): string
    {
        return match ($this) {
            self::ASSET,
            self::LIABILITY,
            self::EQUITY => 'balance_sheet',

            self::REVENUE,
            self::COST_OF_SALES,
            self::EXPENSE,
            self::OTHER_INCOME,
            self::OTHER_EXPENSE => 'income_statement',
        };
    }
}
