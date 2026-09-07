<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,

            'name' => $this->name,

            'description' => $this->description,

            'timezone' => $this->timezone,

            'default_start_time' => $this->default_start_time,

            'default_end_time' => $this->default_end_time,

            'break_minutes' => $this->break_minutes,

            'late_grace_minutes' => $this->late_grace_minutes,

            'early_leave_grace_minutes' => $this->early_leave_grace_minutes,

            'is_active' => $this->is_active,

            'days' => $this->whenLoaded(
                'days',
                fn () => $this->days
                    ->sortBy(
                        fn ($day) => $day->day_of_week->value
                    )
                    ->values()
                    ->map(
                        fn ($day) => [
                            'day_of_week' => $day->day_of_week->value,

                            'day_name' => $day->day_of_week->name,

                            'is_working_day' => $day->is_working_day,

                            'start_time' => $day->start_time,

                            'end_time' => $day->end_time,

                            'break_minutes' => $day->break_minutes,
                        ]
                    )
                    ->all()
            ),
        ];
    }
}
