<?php

namespace Modules\HRM\Application\Actions\Overtimes;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Domain\Enums\OvertimeStatus;
use Modules\HRM\Models\Overtime;

final class RejectOvertimeAction
{
    public function execute(
        Overtime $overtime,
        int $rejectedBy,
        string $reason
    ): Overtime {
        if (! $overtime->isPending()) {
            throw new DomainException(
                'Only pending overtime can be rejected.'
            );
        }

        return DB::transaction(
            function () use (
                $overtime,
                $rejectedBy,
                $reason
            ): Overtime {
                $overtime->update([
                    'status' => OvertimeStatus::REJECTED,
                    'rejected_by' => $rejectedBy,
                    'rejected_at' => now(),
                    'rejection_reason' => $reason,
                    'approved_by' => null,
                    'approved_at' => null,
                    'updated_by' => $rejectedBy,
                ]);

                return $overtime->refresh();
            }
        );
    }
}
