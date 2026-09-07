<?php

namespace Modules\HRM\Application\DTOs\Attendances;

use Modules\HRM\Domain\Enums\AttendanceSource;
use Modules\HRM\Domain\Enums\AttendanceStatus;

final readonly class CreateAttendanceData
{
    public function __construct(
        public int $tenantId,
        public int $tenantStaffId,
        public string $attendanceDate,
        public ?string $checkIn,
        public ?string $checkOut,
        public int $lateMinutes,
        public int $earlyLeaveMinutes,
        public int $overtimeMinutes,
        public AttendanceStatus $status,
        public AttendanceSource $source,
        public ?string $notes,
        public int $createdBy,
    ) {}
}
