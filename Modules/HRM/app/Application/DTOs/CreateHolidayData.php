<?php

namespace Modules\HRM\Application\DTOs;

use Modules\HRM\Domain\Enums\HolidayType;

final readonly class CreateHolidayData
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $startDate,
        public ?string $endDate,
        public HolidayType $type,
        public ?string $description,
        public bool $isRecurring,
    ) {}
}
