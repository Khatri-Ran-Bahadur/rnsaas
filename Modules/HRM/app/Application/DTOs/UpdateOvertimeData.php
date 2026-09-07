<?php

namespace Modules\HRM\Application\DTOs;

use Modules\HRM\Domain\Enums\OvertimeType;

final readonly class UpdateOvertimeData
{
    public function __construct(
        public int $tenantStaffId,
        public string $date,
        public string $startTime,
        public string $endTime,
        public int $totalMinutes,
        public OvertimeType $type,
        public float $rateMultiplier,
        public ?string $reason,
        public bool $isActive,
        public int $updatedBy,
    ) {}
}
