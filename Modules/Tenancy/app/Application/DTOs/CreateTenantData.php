<?php

namespace Modules\Tenancy\Application\DTOs;

use Modules\Tenancy\Domain\Enums\TenantStatus;

final readonly class CreateTenantData
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $industry = null,
        public TenantStatus $status = TenantStatus::Pending,
        public ?string $countryCode = null,
        public string $timezone = 'UTC',
        public string $locale = 'en',
        public string $currency = 'USD',
        public array $settings = [],
    ) {}
}
