<?php

namespace Modules\Tenancy\Application\Actions\Staff;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Models\TenantStaff;

final class SuspendStaffAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function execute(TenantStaff $staff): TenantStaff
    {
        abort_unless(
            $staff->tenant_id === $this->currentTenant->id(),
            404
        );

        $staff->update([
            'employment_status' => EmploymentStatus::Suspended,
            'suspended_at' => now(),
        ]);

        return $staff->refresh();
    }
}
