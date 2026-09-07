<?php

namespace Modules\HRM\Application\Actions\Leaves;

use Illuminate\Support\Facades\DB;
use Modules\HRM\Domain\Enums\LeaveStatus;
use Modules\HRM\Models\LeaveRequest;

final class RejectLeaveAction
{
    public function execute(
        LeaveRequest $leave,
        int $tenantId,
        int $rejectedBy,
        string $reason,
    ): LeaveRequest {
        abort_unless(
            $leave->tenant_id === $tenantId,
            404,
        );

        abort_if(
            ! $leave->isPending(),
            422,
            'Only pending leave requests can be rejected.',
        );

        return DB::transaction(
            function () use (
                $leave,
                $rejectedBy,
                $reason,
            ): LeaveRequest {
                $leave->update([
                    'status' => LeaveStatus::REJECTED,
                    'rejected_by' => $rejectedBy,
                    'rejected_at' => now(),
                    'rejection_reason' => $reason,
                    'approved_by' => null,
                    'approved_at' => null,
                    'updated_by' => $rejectedBy,
                ]);

                return $leave->refresh();
            },
        );
    }
}
