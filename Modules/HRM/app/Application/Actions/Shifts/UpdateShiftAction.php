<?php

namespace Modules\HRM\Application\Actions\Shifts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Application\DTOs\UpdateShiftData;
use Modules\HRM\Models\Shift;

final class UpdateShiftAction
{
    public function execute(UpdateShiftData $data): Shift
    {
        return DB::transaction(function () use ($data): Shift {
            $shift = Shift::query()
                ->forTenant($data->tenantId)
                ->where('public_id', $data->publicId)
                ->first();

            if (! $shift) {
                throw (new ModelNotFoundException)
                    ->setModel(Shift::class);
            }

            $shift->update([
                'name' => $data->name,
                'code' => $data->code,
                'description' => $data->description,
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
                'break_minutes' => $data->breakMinutes,
                'late_grace_minutes' => $data->lateGraceMinutes,
                'early_leave_grace_minutes' => $data->earlyLeaveGraceMinutes,
                'is_overnight' => $data->isOvernight,
                'is_active' => $data->isActive,
            ]);

            return $shift->refresh();
        });
    }
}
