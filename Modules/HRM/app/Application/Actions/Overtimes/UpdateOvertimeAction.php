<?php

namespace Modules\HRM\Application\Actions\Overtimes;

use Modules\HRM\Application\DTOs\UpdateOvertimeData;
use Modules\HRM\Models\Overtime;
use Modules\Tenancy\Models\TenantStaff;

final class UpdateOvertimeAction
{
    public function execute(
        Overtime $overtime,
        UpdateOvertimeData $data
    ): Overtime {
        $staff = TenantStaff::query()
            ->where('id', $data->tenantStaffId)
            ->where(
                'tenant_id',
                $overtime->tenant_id
            )
            ->where('employment_status', 'active')
            ->firstOrFail();

        $overtime->update([
            'tenant_staff_id' => $staff->id,
            'date' => $data->date,
            'start_time' => $data->startTime,
            'end_time' => $data->endTime,
            'total_minutes' => $data->totalMinutes,
            'type' => $data->type,
            'rate_multiplier' => $data->rateMultiplier,
            'reason' => $data->reason,
            'is_active' => $data->isActive,
            'updated_by' => $data->updatedBy,
        ]);

        return $overtime->refresh();
    }
}
