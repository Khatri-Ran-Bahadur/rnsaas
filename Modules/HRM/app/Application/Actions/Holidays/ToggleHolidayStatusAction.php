<?php

namespace Modules\HRM\Application\Actions\Holidays;

use Modules\HRM\Models\Holiday;

final class ToggleHolidayStatusAction
{
    public function execute(Holiday $holiday): Holiday
    {
        $holiday->update([
            'is_active' => ! $holiday->is_active,
        ]);

        return $holiday->refresh();
    }
}
