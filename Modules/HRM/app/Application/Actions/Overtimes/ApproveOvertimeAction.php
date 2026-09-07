<?php

namespace Modules\HRM\Application\Actions\Overtimes;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Domain\Enums\OvertimeStatus;
use Modules\HRM\Models\Overtime;

final class ApproveOvertimeAction
{
    public function execute(
        Overtime $overtime,
        int $approvedBy
    ): Overtime {
        if (! $overtime->isPending()) {
            throw new DomainException(
                'Only pending overtime can be approved.'
            );
        }

        return DB::transaction(
            function () use (
                $overtime,
                $approvedBy
            ): Overtime {
                $overtime->update([
                    'status' => OvertimeStatus::APPROVED,
                    'approved_by' => $approvedBy,
                    'approved_at' => now(),
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                    'updated_by' => $approvedBy,
                ]);

                return $overtime->refresh();
            }
        );
    }
}
