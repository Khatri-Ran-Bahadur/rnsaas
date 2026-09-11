<?php

namespace Modules\Accounting\Application\DTOs\Vendors;

final readonly class CreateVendorData
{
    public function __construct(
        public string $vendorCode,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $taxNumber,
        public ?string $billingAddressLine1,
        public ?string $billingAddressLine2,
        public ?string $billingCity,
        public ?string $billingState,
        public ?string $billingPostcode,
        public ?string $billingCountry,
        public ?int $payableAccountId,
        public string $creditLimit,
        public int $paymentTermsDays,
    ) {}
}
