<?php

namespace Modules\HRM\Application\DTOs;

final readonly class CreateShiftData
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $code,
        public ?string $description,
        public string $startTime,
        public string $endTime,
        public int $breakMinutes,
        public int $lateGraceMinutes,
        public int $earlyLeaveGraceMinutes,
        public bool $isOvernight,
    ) {}
}
