<?php

namespace Modules\Accounting\Application\DTOs\Journal;

final readonly class CreateJournalLineData
{
    public function __construct(
        public int $accountId,
        public int $lineNumber,
        public string $lineType,
        public mixed $amount,
        public ?string $description = null,
    ) {}
}
