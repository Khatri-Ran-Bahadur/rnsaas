<?php

namespace Modules\Accounting\Application\DTOs\PurchaseBills;

final readonly class PurchaseBillLineData
{
    public function __construct(
        public int $lineNumber,
        public string $description,
        public string $quantity,
        public string $unitPrice,
        public string $discountAmount,
        public string $taxRate,
        public string $taxAmount,
        public string $subtotal,
        public string $total,
        public int $debitAccountId,
        public ?int $taxAccountId,
    ) {}
}
