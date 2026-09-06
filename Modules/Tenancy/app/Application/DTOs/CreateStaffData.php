<?php

namespace Modules\Tenancy\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;

final readonly class CreateStaffData
{
    public function __construct(
        public ?int $userId,
        public ?string $name,
        public ?string $email,
        public ?string $phone,
        public string $employeeCode,
        public int $branchId,
        public int $departmentId,
        public int $designationId,
        public ?CarbonImmutable $joiningDate,
        public EmploymentStatus $employmentStatus = EmploymentStatus::Active,
    ) {}
}
