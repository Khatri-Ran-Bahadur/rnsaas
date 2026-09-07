<?php

namespace Modules\HRM\Application\Actions\Holidays;

use Modules\HRM\Application\DTOs\CreateHolidayData;
use Modules\HRM\Models\Holiday;

final class CreateHolidayAction
{
    public function execute(CreateHolidayData $data): Holiday
    {
        return Holiday::query()->create([
            'tenant_id' => $data->tenantId,
            'name' => $data->name,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'type' => $data->type,
            'description' => $data->description,
            'is_recurring' => $data->isRecurring,
            'is_active' => true,
        ]);
    }
}
