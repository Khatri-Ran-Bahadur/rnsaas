<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HRM\Domain\Enums\OvertimeStatus;
use Modules\HRM\Domain\Enums\OvertimeType;

class IndexOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('overtime.view') ?? false;
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

            'type' => [
                'nullable',
                'string',
                'in:'.implode(
                    ',',
                    array_column(
                        OvertimeType::cases(),
                        'value'
                    )
                ),
            ],

            'status' => [
                'nullable',
                'string',
                'in:'.implode(
                    ',',
                    array_column(
                        OvertimeStatus::cases(),
                        'value'
                    )
                ),
            ],

            'active' => [
                'nullable',
                'boolean',
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

            'per_page' => [
                'nullable',
                'integer',
                'min:10',
                'max:100',
            ],
        ];
    }
}
