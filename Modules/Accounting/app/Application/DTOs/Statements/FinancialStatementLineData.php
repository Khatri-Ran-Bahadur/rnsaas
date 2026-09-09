<?php

namespace Modules\Accounting\Application\DTOs\Statements;

use Modules\Accounting\Domain\Enums\FinancialStatementSection;

final readonly class FinancialStatementLineData
{
    public function __construct(
        public int $accountId,
        public string $accountCode,
        public string $accountName,
        public FinancialStatementSection $section,
        public string $debit,
        public string $credit,
        public string $amount,
    ) {}
}
