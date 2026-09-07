<?php

namespace Modules\HRM\Application\Actions\Leaves;

use Illuminate\Support\Facades\DB;
use Modules\HRM\Domain\Enums\LeaveStatus;
use Modules\HRM\Models\LeaveRequest;

final class ApproveLeaveAction
{
    public function execute(
        LeaveRequest $leave,
        int $tenantId,
        int $approvedBy,
    ): LeaveRequest {
        abort_unless(
            $leave->tenant_id === $tenantId,
            404,
        );

        abort_if(
            ! $leave->isPending(),
            422,
            'Only pending leave requests can be approved.',
        );

        return DB::transaction(
            function () use (
                $leave,
                $approvedBy,
            ): LeaveRequest {
                $leave->update([
                    'status' => LeaveStatus::APPROVED,
                    'approved_by' => $approvedBy,
                    'approved_at' => now(),
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                    'updated_by' => $approvedBy,
                ]);

                return $leave->refresh();
            },
        );
    }
}
