<?php

namespace Modules\Tenancy\Application\DTOs;

use Carbon\CarbonImmutable;

final readonly class CreateStaffData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $employeeCode,
        public string $designation,
        public ?string $baseSalary,
        public ?CarbonImmutable $joiningDate,
        public int $branchId,
    ) {}
}
