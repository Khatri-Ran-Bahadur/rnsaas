<?php

namespace Modules\HRM\Application\Actions\Holidays;

use Modules\HRM\Application\DTOs\UpdateHolidayData;
use Modules\HRM\Models\Holiday;

final class UpdateHolidayAction
{
    public function execute(
        Holiday $holiday,
        UpdateHolidayData $data
    ): Holiday {
        $holiday->update([
            'name' => $data->name,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'type' => $data->type,
            'description' => $data->description,
            'is_recurring' => $data->isRecurring,
            'is_active' => $data->isActive,
        ]);

        return $holiday->refresh();
    }
}
