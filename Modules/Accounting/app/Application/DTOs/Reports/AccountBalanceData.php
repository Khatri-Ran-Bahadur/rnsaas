<?php

namespace Modules\Accounting\Application\DTOs\Reports;

final readonly class AccountBalanceData
{
    public function __construct(
        public int $accountId,
        public string $accountCode,
        public string $accountName,
        public string $normalBalance,
        public string $debit,
        public string $credit,
        public string $balance,
    ) {}
}
