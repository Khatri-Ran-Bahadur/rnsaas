<?php

namespace Modules\HRM\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\HRM\Application\DTOs\CreateWorkScheduleData;
use Modules\HRM\Models\WorkSchedule;

final class CreateWorkScheduleAction
{
    public function execute(
        CreateWorkScheduleData $data
    ): WorkSchedule {
        return DB::transaction(function () use ($data): WorkSchedule {
            $schedule = WorkSchedule::query()->create([
                'tenant_id' => $data->tenantId,
                'name' => $data->name,
                'description' => $data->description,
                'timezone' => $data->timezone,
                'default_start_time' => $data->defaultStartTime,
                'default_end_time' => $data->defaultEndTime,
                'break_minutes' => $data->breakMinutes,
                'late_grace_minutes' => $data->lateGraceMinutes,
                'early_leave_grace_minutes' => $data->earlyLeaveGraceMinutes,
                'is_active' => true,
            ]);

            $schedule->days()->createMany($data->days);

            return $schedule->load('days');
        });
    }
}
