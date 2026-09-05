<?php

namespace Modules\Tenancy\Application\Actions\Organization;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Application\DTOs\UpdateDepartmentData;
use Modules\Tenancy\Models\Department;

final class UpdateDepartmentAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(
        Department $department,
        UpdateDepartmentData $data,
    ): Department {
        abort_unless(
            $department->tenant_id === $this->currentTenant->id(),
            404
        );

        $department->update([
            'name' => $data->name,
            'code' => strtoupper($data->code),
        ]);

        return $department->refresh();
    }
}
