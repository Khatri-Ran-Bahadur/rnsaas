<?php

namespace Modules\HRM\Application\Actions\Leaves;

use Carbon\Carbon;
use Modules\HRM\Application\DTOs\Leaves\CreateLeaveData;
use Modules\HRM\Domain\Enums\LeaveStatus;
use Modules\HRM\Models\LeaveRequest;
use Modules\Tenancy\Models\TenantStaff;

final class CreateLeaveAction
{
    public function execute(
        CreateLeaveData $data,
    ): LeaveRequest {
        $staff = TenantStaff::query()
            ->whereKey($data->tenantStaffId)
            ->where('tenant_id', $data->tenantId)
            ->where('employment_status', 'active')
            ->firstOrFail();

        $start = Carbon::parse($data->startDate);
        $end = Carbon::parse($data->endDate);

        $this->ensureNoOverlap(
            $data->tenantId,
            $staff->id,
            $start,
            $end,
        );

        $totalDays = $start->diffInDays($end) + 1;

        return LeaveRequest::query()->create([
            'tenant_id' => $data->tenantId,
            'tenant_staff_id' => $staff->id,
            'leave_type' => $data->leaveType,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'total_days' => $totalDays,
            'reason' => $data->reason,
            'status' => LeaveStatus::PENDING,
            'created_by' => $data->createdBy,
            'updated_by' => $data->createdBy,
            'is_active' => true,
        ]);
    }

    private function ensureNoOverlap(
        int $tenantId,
        int $staffId,
        Carbon $start,
        Carbon $end,
    ): void {
        $overlapExists = LeaveRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('tenant_staff_id', $staffId)
            ->where('is_active', true)
            ->whereIn('status', [
                LeaveStatus::PENDING,
                LeaveStatus::APPROVED,
            ])
            ->where(function ($query) use ($start, $end): void {
                $query
                    ->whereDate('start_date', '<=', $end)
                    ->whereDate('end_date', '>=', $start);
            })
            ->exists();

        abort_if(
            $overlapExists,
            422,
            'The employee already has an overlapping leave request.',
        );
    }
}
