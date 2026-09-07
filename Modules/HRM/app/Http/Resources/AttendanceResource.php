<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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

            'attendance_date' => $this->attendance_date?->toDateString(),

            'check_in' => $this->check_in,
            'check_out' => $this->check_out,

            'worked_minutes' => $this->worked_minutes,
            'worked_hours' => $this->workedHours(),

            'late_minutes' => $this->late_minutes,
            'early_leave_minutes' => $this->early_leave_minutes,
            'overtime_minutes' => $this->overtime_minutes,

            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],

            'source' => [
                'value' => $this->source?->value,
                'label' => $this->source?->label(),
            ],

            'notes' => $this->notes,

            'is_active' => $this->is_active,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
