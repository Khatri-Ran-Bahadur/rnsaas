<?php

namespace Modules\Accounting\Application\DTOs\Customers;

final readonly class CreateCustomerData
{
    public function __construct(
        public string $name,
        public string $customerCode,
        public ?string $email,
        public ?string $phone,
        public ?string $taxNumber,
        public ?string $billingAddressLine1,
        public ?string $billingAddressLine2,
        public ?string $billingCity,
        public ?string $billingState,
        public ?string $billingPostcode,
        public ?string $billingCountry,
        public ?int $receivableAccountId,
        public string $creditLimit,
        public int $paymentTermsDays,
    ) {}
}
