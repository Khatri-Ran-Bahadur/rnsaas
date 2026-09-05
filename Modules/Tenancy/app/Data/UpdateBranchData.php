<?php

namespace Modules\Tenancy\Data;

use Modules\Tenancy\Domain\Enums\BranchStatus;

final readonly class UpdateBranchData
{
    public function __construct(
        public string $name,
        public string $code,
        public BranchStatus $status = BranchStatus::Active,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $postalCode = null,
        public ?string $countryCode = null,
    ) {}
}
