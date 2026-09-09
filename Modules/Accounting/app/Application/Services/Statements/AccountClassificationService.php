<?php

namespace Modules\Accounting\Application\Services\Statements;

use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use Modules\Accounting\Models\Account;
use RuntimeException;

final class AccountClassificationService
{
    public function section(Account $account): FinancialStatementSection
    {
        if ($account->financial_statement_section !== null) {
            return $account->financial_statement_section;
        }

        $accountType = $account->accountType;

        if ($accountType?->financial_statement_section !== null) {
            return $accountType->financial_statement_section;
        }

        throw new RuntimeException(
            "Account [{$account->code}] has no financial statement classification."
        );
    }
}
