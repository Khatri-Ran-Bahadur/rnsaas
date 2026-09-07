<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HRM\Models\Overtime;

/** @mixin Overtime */
class OvertimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'tenant_staff_id' => $this->tenant_staff_id,

            'staff' => $this->whenLoaded(
                'staff',
                fn () => [
                    'id' => $this->staff->id,
                    'public_id' => $this->staff->public_id,
                    'employee_code' => $this->staff->employee_code,
                    'name' => $this->staff->user?->name,
                ]
            ),

            'date' => $this->date?->toDateString(),

            'start_time' => $this->start_time,

            'end_time' => $this->end_time,

            'total_minutes' => $this->total_minutes,

            'total_hours' => $this->totalHours(),

            'type' => $this->type?->value,

            'type_label' => $this->type?->label(),

            'status' => $this->status?->value,

            'status_label' => $this->status?->label(),

            'rate_multiplier' => $this->rate_multiplier,

            'reason' => $this->reason,

            'is_active' => $this->is_active,

            'created_by' => $this->created_by,

            'updated_by' => $this->updated_by,

            'approved_by' => $this->approved_by,

            'approved_at' => $this->approved_at?->toISOString(),

            'rejected_by' => $this->rejected_by,

            'rejected_at' => $this->rejected_at?->toISOString(),

            'rejection_reason' => $this->rejection_reason,

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
