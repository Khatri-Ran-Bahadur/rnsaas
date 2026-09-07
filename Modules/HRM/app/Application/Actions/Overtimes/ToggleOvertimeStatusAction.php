<?php

namespace Modules\HRM\Application\Actions\Overtimes;

use Modules\HRM\Models\Overtime;

final class ToggleOvertimeStatusAction
{
    public function execute(
        Overtime $overtime,
        int $updatedBy
    ): Overtime {
        $overtime->update([
            'is_active' => ! $overtime->is_active,
            'updated_by' => $updatedBy,
        ]);

        return $overtime->refresh();
    }
}
