<?php

namespace Modules\Accounting\Application\DTOs\Reports;

use Carbon\CarbonImmutable;

final readonly class AccountStatementData
{
    public function __construct(
        public int $accountId,
        public string $accountCode,
        public string $accountName,
        public string $normalBalance,
        public CarbonImmutable $fromDate,
        public CarbonImmutable $toDate,
        public string $openingBalance,
        public string $totalDebit,
        public string $totalCredit,
        public string $closingBalance,
        public array $lines,
    ) {}
}
