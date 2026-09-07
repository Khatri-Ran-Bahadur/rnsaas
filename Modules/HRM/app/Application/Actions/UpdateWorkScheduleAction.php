<?php

namespace Modules\HRM\Application\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Application\DTOs\UpdateWorkScheduleData;
use Modules\HRM\Models\WorkSchedule;

final class UpdateWorkScheduleAction
{
    public function execute(
        UpdateWorkScheduleData $data
    ): WorkSchedule {
        return DB::transaction(function () use ($data): WorkSchedule {
            $schedule = WorkSchedule::query()
                ->forTenant($data->tenantId)
                ->where('public_id', $data->publicId)
                ->first();

            if (! $schedule) {
                throw (new ModelNotFoundException)
                    ->setModel(WorkSchedule::class);
            }

            $schedule->update([
                'name' => $data->name,
                'description' => $data->description,
                'timezone' => $data->timezone,
                'default_start_time' => $data->defaultStartTime,
                'default_end_time' => $data->defaultEndTime,
                'break_minutes' => $data->breakMinutes,
                'late_grace_minutes' => $data->lateGraceMinutes,
                'early_leave_grace_minutes' => $data->earlyLeaveGraceMinutes,
                'is_active' => $data->isActive,
            ]);

            foreach ($data->days as $day) {
                $schedule->days()->updateOrCreate(
                    [
                        'day_of_week' => $day['day_of_week'],
                    ],
                    [
                        'is_working_day' => $day['is_working_day'],
                        'start_time' => $day['start_time'],
                        'end_time' => $day['end_time'],
                        'break_minutes' => $day['break_minutes'],
                    ],
                );
            }

            return $schedule->load('days');
        });
    }
}
