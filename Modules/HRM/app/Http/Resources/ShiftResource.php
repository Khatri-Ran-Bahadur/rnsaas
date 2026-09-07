<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,

            'name' => $this->name,

            'code' => $this->code,

            'description' => $this->description,

            'start_time' => $this->start_time,

            'end_time' => $this->end_time,

            'break_minutes' => $this->break_minutes,

            'late_grace_minutes' => $this->late_grace_minutes,

            'early_leave_grace_minutes' => $this->early_leave_grace_minutes,

            'is_overnight' => $this->is_overnight,

            'is_active' => $this->is_active,
        ];
    }
}
