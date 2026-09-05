<?php

namespace Modules\Tenancy\Application\Actions\Organization;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Domain\Enums\DepartmentStatus;
use Modules\Tenancy\Models\Department;

final class ActivateDepartmentAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(Department $department): Department
    {
        abort_unless(
            $department->tenant_id === $this->currentTenant->id(),
            404
        );

        $department->update([
            'status' => DepartmentStatus::Active,
        ]);

        return $department->refresh();
    }
}
