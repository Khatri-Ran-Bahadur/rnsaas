<?php

namespace Modules\Accounting\Application\DTOs\PurchaseBills;

use DateTimeInterface;

final readonly class CreatePurchaseBillData
{
    public function __construct(
        public int $vendorId,
        public string $billNumber,
        public DateTimeInterface $billDate,
        public DateTimeInterface $dueDate,
        public string $currency,
        public string $reference,
        public ?string $notes,
        public array $lines,
    ) {}
}
