<?php

namespace Modules\HRM\Application\Actions\Overtimes;

use Modules\HRM\Application\DTOs\CreateOvertimeData;
use Modules\HRM\Models\Overtime;
use Modules\Tenancy\Models\TenantStaff;

final class CreateOvertimeAction
{
    public function execute(
        CreateOvertimeData $data
    ): Overtime {
        $staff = TenantStaff::query()
            ->where('id', $data->tenantStaffId)
            ->where('tenant_id', $data->tenantId)
            ->where('employment_status', 'active')
            ->firstOrFail();

        return Overtime::query()->create([
            'tenant_id' => $data->tenantId,
            'tenant_staff_id' => $staff->id,
            'date' => $data->date,
            'start_time' => $data->startTime,
            'end_time' => $data->endTime,
            'total_minutes' => $data->totalMinutes,
            'type' => $data->type,
            'status' => 'pending',
            'rate_multiplier' => $data->rateMultiplier,
            'reason' => $data->reason,
            'created_by' => $data->createdBy,
            'updated_by' => $data->createdBy,
            'is_active' => true,
        ]);
    }
}
