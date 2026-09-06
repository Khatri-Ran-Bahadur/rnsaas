<?php

namespace Modules\Tenancy\Application\DTOs;

final readonly class UpdateCompanyProfileData
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $industry,
        public ?string $countryCode,
        public string $timezone,
        public string $locale,
        public string $currency,
        public array $settings = [],
    ) {}
}
