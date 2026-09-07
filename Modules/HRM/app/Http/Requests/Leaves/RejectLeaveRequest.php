<?php

namespace Modules\HRM\Http\Requests\Leaves;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('leave.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    public function reason(): string
    {
        return (string) $this->input('rejection_reason');
    }
}
