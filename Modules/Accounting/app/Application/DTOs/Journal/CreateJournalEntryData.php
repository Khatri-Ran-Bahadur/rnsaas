<?php

namespace Modules\Accounting\Application\DTOs\Journal;

final readonly class CreateJournalEntryData
{
    /**
     * @param array<int, CreateJournalLineData|array{
     *     account_id:int,
     *     line_type:string,
     *     amount:numeric,
     *     description?:string|null
     * }> $lines
     */
    public function __construct(
        public int $tenantId,
        public ?int $fiscalYearId,
        public ?int $accountingPeriodId,
        public string $entryNumber,
        public mixed $entryDate,
        public string $description,
        public ?string $referenceType = null,
        public mixed $referenceId = null,
        public ?string $idempotencyKey = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}
