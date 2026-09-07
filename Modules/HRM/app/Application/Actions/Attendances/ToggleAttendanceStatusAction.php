<?php

namespace Modules\HRM\Application\Actions\Attendances;

use Modules\HRM\Models\Attendance;

final class ToggleAttendanceStatusAction
{
    public function execute(
        Attendance $attendance,
        int $tenantId,
        int $updatedBy,
    ): Attendance {
        abort_unless(
            $attendance->tenant_id === $tenantId,
            404,
        );

        $attendance->update([
            'is_active' => ! $attendance->is_active,
            'updated_by' => $updatedBy,
        ]);

        return $attendance->refresh();
    }
}
