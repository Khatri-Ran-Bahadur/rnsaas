<?php

namespace Modules\HRM\Http\Requests\Leaves;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HRM\Domain\Enums\LeaveStatus;
use Modules\HRM\Domain\Enums\LeaveType;

class IndexLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('leave.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'tenant_staff_id' => [
                'nullable',
                'integer',
            ],

            'leave_type' => [
                'nullable',
                Rule::enum(LeaveType::class),
            ],

            'status' => [
                'nullable',
                Rule::enum(LeaveStatus::class),
            ],

            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],

            'active' => [
                'nullable',
                'boolean',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:10',
                'max:100',
            ],
        ];
    }
}
