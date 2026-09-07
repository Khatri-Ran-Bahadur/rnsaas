<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,

            'staff' => [
                'public_id' => $this->staff?->public_id,
                'name' => $this->staff?->user?->name,
                'employee_code' => $this->staff?->employee_code,
            ],

            'leave_type' => [
                'value' => $this->leave_type?->value,
                'label' => $this->leave_type?->label(),
            ],

            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'total_days' => (float) $this->total_days,

            'reason' => $this->reason,

            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],

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
