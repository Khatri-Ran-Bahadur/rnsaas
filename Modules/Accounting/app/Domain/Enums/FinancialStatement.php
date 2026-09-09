<?php

namespace Modules\Accounting\Domain\Enums;

enum FinancialStatement: string
{
    case BALANCE_SHEET = 'balance_sheet';

    case PROFIT_AND_LOSS = 'profit_and_loss';

    case CASH_FLOW = 'cash_flow';

    public function label(): string
    {
        return match ($this) {
            self::BALANCE_SHEET => 'Balance Sheet',
            self::PROFIT_AND_LOSS => 'Profit & Loss',
            self::CASH_FLOW => 'Cash Flow',
        };
    }
}
