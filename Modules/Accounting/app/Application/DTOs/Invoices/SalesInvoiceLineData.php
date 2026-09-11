<?php

namespace Modules\Accounting\Application\DTOs\Invoices;

final readonly class SalesInvoiceLineData
{
    public function __construct(
        public int $lineNumber,
        public string $description,
        public string|float|int $quantity,
        public string|float|int $unitPrice,
        public string|float|int $discountAmount,
        public string|float|int $taxRate,
        public string|float|int $taxAmount,
        public string|float|int $subtotal,
        public string|float|int $total,
        public int $revenueAccountId,
    ) {}
}
