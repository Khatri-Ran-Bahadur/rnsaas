<?php

namespace Modules\Accounting\Domain\Enums;

enum FinancialStatementSection: string
{
    case ASSETS = 'assets';
    case LIABILITIES = 'liabilities';
    case EQUITY = 'equity';

    case REVENUE = 'revenue';
    case COST_OF_SALES = 'cost_of_sales';
    case OPERATING_EXPENSES = 'operating_expenses';
    case OTHER_INCOME = 'other_income';
    case OTHER_EXPENSES = 'other_expenses';

    public function statement(): FinancialStatement
    {
        return match ($this) {
            self::ASSETS,
            self::LIABILITIES,
            self::EQUITY => FinancialStatement::BALANCE_SHEET,

            self::REVENUE,
            self::COST_OF_SALES,
            self::OPERATING_EXPENSES,
            self::OTHER_INCOME,
            self::OTHER_EXPENSES => FinancialStatement::PROFIT_AND_LOSS,
        };
    }
}
