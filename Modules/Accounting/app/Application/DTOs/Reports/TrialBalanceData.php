<?php

namespace Modules\Accounting\Application\DTOs\Reports;

use Carbon\CarbonImmutable;

final readonly class TrialBalanceData
{
    public function __construct(
        public CarbonImmutable $fromDate,
        public CarbonImmutable $toDate,
        public array $accounts,
        public string $totalDebit,
        public string $totalCredit,
    ) {}
}