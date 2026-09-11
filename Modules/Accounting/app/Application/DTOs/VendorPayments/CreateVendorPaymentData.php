<?php

namespace Modules\Accounting\Application\DTOs\VendorPayments;

use DateTimeInterface;

final readonly class CreateVendorPaymentData
{
    public function __construct(
        public int $vendorId,
        public string $paymentNumber,
        public DateTimeInterface $paymentDate,
        public string $amount,
        public string $currency,
        public int $bankAccountId,
        public string $paymentMethod,
        public ?string $reference,
        public ?string $notes,
    ) {}
}
