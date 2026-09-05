<?php

namespace Modules\Tenancy\Application\DTOs;

final readonly class CreateBranchData
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $postalCode = null,
        public ?string $countryCode = null,
    ) {}
}
