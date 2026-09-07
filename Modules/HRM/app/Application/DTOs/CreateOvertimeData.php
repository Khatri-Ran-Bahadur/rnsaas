<?php

namespace Modules\HRM\Application\DTOs;

use Modules\HRM\Domain\Enums\OvertimeType;

final readonly class CreateOvertimeData
{
    public function __construct(
        public int $tenantId,
        public int $tenantStaffId,
        public string $date,
        public string $startTime,
        public string $endTime,
        public int $totalMinutes,
        public OvertimeType $type,
        public float $rateMultiplier,
        public ?string $reason,
        public int $createdBy,
    ) {}
}
