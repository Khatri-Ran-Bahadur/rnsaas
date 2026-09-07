<?php

namespace Modules\HRM\Application\DTOs\Leaves;

use Modules\HRM\Domain\Enums\LeaveType;

final readonly class UpdateLeaveData
{
    public function __construct(
        public int $tenantId,
        public int $tenantStaffId,
        public LeaveType $leaveType,
        public string $startDate,
        public string $endDate,
        public ?string $reason,
        public bool $isActive,
        public int $updatedBy,
    ) {}
}
