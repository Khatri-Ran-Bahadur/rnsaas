<?php

namespace Modules\HRM\Application\Actions\Attendances;

use Carbon\Carbon;
use Modules\HRM\Application\DTOs\Attendances\UpdateAttendanceData;
use Modules\HRM\Models\Attendance;
use Modules\Tenancy\Models\TenantStaff;

final class UpdateAttendanceAction
{
    public function execute(
        Attendance $attendance,
        UpdateAttendanceData $data,
    ): Attendance {
        abort_unless(
            $attendance->tenant_id === $data->tenantId,
            404,
        );

        $staff = TenantStaff::query()
            ->whereKey($data->tenantStaffId)
            ->where('tenant_id', $data->tenantId)
            ->where('employment_status', 'active')
            ->firstOrFail();

        $workedMinutes = 0;

        if ($data->checkIn && $data->checkOut) {
            $start = Carbon::createFromFormat(
                'H:i',
                $data->checkIn,
            );

            $end = Carbon::createFromFormat(
                'H:i',
                $data->checkOut,
            );

            if ($end->lessThan($start)) {
                $end->addDay();
            }

            $workedMinutes = (int) $start->diffInMinutes($end);
        }

        $attendance->update([
            'tenant_staff_id' => $staff->id,
            'attendance_date' => $data->attendanceDate,
            'check_in' => $data->checkIn,
            'check_out' => $data->checkOut,
            'worked_minutes' => $workedMinutes,
            'late_minutes' => $data->lateMinutes,
            'early_leave_minutes' => $data->earlyLeaveMinutes,
            'overtime_minutes' => $data->overtimeMinutes,
            'status' => $data->status,
            'source' => $data->source,
            'notes' => $data->notes,
            'is_active' => $data->isActive,
            'updated_by' => $data->updatedBy,
        ]);

        return $attendance->refresh();
    }
}
