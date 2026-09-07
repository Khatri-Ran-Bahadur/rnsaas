<?php

namespace Modules\HRM\Application\Actions\Attendances;

use Carbon\Carbon;
use Modules\HRM\Application\DTOs\Attendances\CreateAttendanceData;
use Modules\HRM\Models\Attendance;
use Modules\Tenancy\Models\TenantStaff;

final class CreateAttendanceAction
{
    public function execute(
        CreateAttendanceData $data,
    ): Attendance {
        $staff = TenantStaff::query()
            ->whereKey($data->tenantStaffId)
            ->where('tenant_id', $data->tenantId)
            ->where('employment_status', 'active')
            ->firstOrFail();

        $workedMinutes = $this->calculateWorkedMinutes(
            $data->checkIn,
            $data->checkOut,
        );

        return Attendance::query()->create([
            'tenant_id' => $data->tenantId,
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
            'created_by' => $data->createdBy,
            'updated_by' => $data->createdBy,
            'is_active' => true,
        ]);
    }

    private function calculateWorkedMinutes(
        ?string $checkIn,
        ?string $checkOut,
    ): int {
        if (! $checkIn || ! $checkOut) {
            return 0;
        }

        $start = Carbon::createFromFormat(
            'H:i',
            $checkIn,
        );

        $end = Carbon::createFromFormat(
            'H:i',
            $checkOut,
        );

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return (int) $start->diffInMinutes($end);
    }
}
