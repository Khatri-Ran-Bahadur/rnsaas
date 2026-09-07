<?php

namespace Modules\HRM\Application\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\HRM\Models\WorkSchedule;

final class ToggleWorkScheduleStatusAction
{
    public function execute(
        int $tenantId,
        string $publicId,
    ): WorkSchedule {
        $schedule = WorkSchedule::query()
            ->forTenant($tenantId)
            ->where('public_id', $publicId)
            ->first();

        if (! $schedule) {
            throw (new ModelNotFoundException)
                ->setModel(WorkSchedule::class);
        }

        $schedule->update([
            'is_active' => ! $schedule->is_active,
        ]);

        return $schedule->refresh();
    }
}
