<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HRM\Models\Holiday;

/** @mixin Holiday */
class HolidayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,

            'start_date' => $this->start_date?->toDateString(),

            'end_date' => $this->end_date?->toDateString(),

            'type' => $this->type?->value,

            'type_label' => $this->type?->label(),

            'description' => $this->description,

            'is_recurring' => $this->is_recurring,

            'is_active' => $this->is_active,

            'is_multi_day' => $this->isMultiDay(),

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
