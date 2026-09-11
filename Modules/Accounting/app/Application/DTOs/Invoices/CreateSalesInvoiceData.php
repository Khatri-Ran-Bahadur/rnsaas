<?php

namespace Modules\Accounting\Application\DTOs\Invoices;

use DateTimeInterface;

final readonly class CreateSalesInvoiceData
{
    /**
     * @param  array<int, SalesInvoiceLineData>  $lines
     */
    public function __construct(
        public int $customerId,
        public string $invoiceNumber,
        public DateTimeInterface|string $invoiceDate,
        public DateTimeInterface|string $dueDate,
        public string $currency,
        public ?string $reference,
        public ?string $notes,
        public array $lines,
    ) {}
}
