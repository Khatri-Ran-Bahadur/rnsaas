<?php

namespace Modules\Accounting\Application\DTOs\Statements;

use Carbon\CarbonImmutable;
use Modules\Accounting\Domain\Enums\FinancialStatement;

final readonly class FinancialStatementData
{
    public function __construct(
        public FinancialStatement $statement,
        public CarbonImmutable $fromDate,
        public CarbonImmutable $toDate,
        public array $sections,
        public string $total,
    ) {}
}
