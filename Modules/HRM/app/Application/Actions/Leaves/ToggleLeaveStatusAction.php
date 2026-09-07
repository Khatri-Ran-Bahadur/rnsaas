<?php

namespace Modules\HRM\Application\Actions\Leaves;

use Modules\HRM\Models\LeaveRequest;

final class ToggleLeaveStatusAction
{
    public function execute(
        LeaveRequest $leave,
        int $tenantId,
        int $updatedBy,
    ): LeaveRequest {
        abort_unless(
            $leave->tenant_id === $tenantId,
            404,
        );

        $leave->update([
            'is_active' => ! $leave->is_active,
            'updated_by' => $updatedBy,
        ]);

        return $leave->refresh();
    }
}
