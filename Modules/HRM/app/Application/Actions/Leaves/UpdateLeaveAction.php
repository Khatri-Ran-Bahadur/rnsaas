<?php

namespace Modules\HRM\Application\Actions\Leaves;

use Carbon\Carbon;
use Modules\HRM\Application\DTOs\Leaves\UpdateLeaveData;
use Modules\HRM\Domain\Enums\LeaveStatus;
use Modules\HRM\Models\LeaveRequest;
use Modules\Tenancy\Models\TenantStaff;

final class UpdateLeaveAction
{
    public function execute(
        LeaveRequest $leave,
        UpdateLeaveData $data,
    ): LeaveRequest {
        abort_unless(
            $leave->tenant_id === $data->tenantId,
            404,
        );

        abort_if(
            $leave->status !== LeaveStatus::PENDING,
            422,
            'Only pending leave requests can be updated.',
        );

        $staff = TenantStaff::query()
            ->whereKey($data->tenantStaffId)
            ->where('tenant_id', $data->tenantId)
            ->where('employment_status', 'active')
            ->firstOrFail();

        $start = Carbon::parse($data->startDate);
        $end = Carbon::parse($data->endDate);

        $overlapExists = LeaveRequest::query()
            ->where('tenant_id', $data->tenantId)
            ->where('tenant_staff_id', $staff->id)
            ->whereKeyNot($leave->id)
            ->where('is_active', true)
            ->whereIn('status', [
                LeaveStatus::PENDING,
                LeaveStatus::APPROVED,
            ])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        abort_if(
            $overlapExists,
            422,
            'The employee already has an overlapping leave request.',
        );

        $leave->update([
            'tenant_staff_id' => $staff->id,
            'leave_type' => $data->leaveType,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'total_days' => $start->diffInDays($end) + 1,
            'reason' => $data->reason,
            'is_active' => $data->isActive,
            'updated_by' => $data->updatedBy,
        ]);

        return $leave->refresh();
    }
}
