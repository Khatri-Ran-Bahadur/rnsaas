<?php

namespace Modules\HRM\Application\Actions\Shifts;

use Modules\HRM\Application\DTOs\CreateShiftData;
use Modules\HRM\Models\Shift;

final class CreateShiftAction
{
    public function execute(CreateShiftData $data): Shift
    {
        return Shift::query()->create([
            'tenant_id' => $data->tenantId,
            'name' => $data->name,
            'code' => $data->code,
            'description' => $data->description,
            'start_time' => $data->startTime,
            'end_time' => $data->endTime,
            'break_minutes' => $data->breakMinutes,
            'late_grace_minutes' => $data->lateGraceMinutes,
            'early_leave_grace_minutes' => $data->earlyLeaveGraceMinutes,
            'is_overnight' => $data->isOvernight,
            'is_active' => true,
        ]);
    }
}
