<?php

namespace Modules\Tenancy\Application\Actions\Organization;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Application\DTOs\CreateDepartmentData;
use Modules\Tenancy\Domain\Enums\DepartmentStatus;
use Modules\Tenancy\Models\Department;

final class CreateDepartmentAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function execute(CreateDepartmentData $data): Department
    {
        return Department::query()->create([
            'tenant_id' => $this->currentTenant->id(),
            'name' => $data->name,
            'code' => strtoupper($data->code),
            'status' => DepartmentStatus::Active,
        ]);
    }
}
