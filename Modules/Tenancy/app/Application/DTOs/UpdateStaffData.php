<?php

namespace Modules\Tenancy\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;

final readonly class UpdateStaffData
{
    public function __construct(
        public string $name,
        public ?string $phone,
        public string $employeeCode,
        public int $branchId,
        public int $departmentId,
        public int $designationId,
        public ?CarbonImmutable $joiningDate,
        public EmploymentStatus $employmentStatus,
    ) {}
}
