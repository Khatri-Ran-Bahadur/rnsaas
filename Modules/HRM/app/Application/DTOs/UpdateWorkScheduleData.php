<?php

namespace Modules\HRM\Application\DTOs;

final readonly class UpdateWorkScheduleData
{
    /**
     * @param array<int, array{
     *     day_of_week: int,
     *     is_working_day: bool,
     *     start_time: string|null,
     *     end_time: string|null,
     *     break_minutes: int
     * }> $days
     */
    public function __construct(
        public int $tenantId,
        public string $publicId,
        public string $name,
        public ?string $description,
        public string $timezone,
        public string $defaultStartTime,
        public string $defaultEndTime,
        public int $breakMinutes,
        public int $lateGraceMinutes,
        public int $earlyLeaveGraceMinutes,
        public bool $isActive,
        public array $days,
    ) {}
}
