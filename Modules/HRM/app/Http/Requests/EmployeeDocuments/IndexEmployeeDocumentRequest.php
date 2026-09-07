<?php

namespace Modules\HRM\Http\Requests\EmployeeDocuments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HRM\Domain\Enums\EmployeeDocumentStatus;
use Modules\HRM\Domain\Enums\EmployeeDocumentType;

class IndexEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee_documents.view') ?? false;
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
                Rule::enum(EmployeeDocumentType::class),
            ],

            'status' => [
                'nullable',
                Rule::enum(EmployeeDocumentStatus::class),
            ],

            'active' => [
                'nullable',
                'boolean',
            ],

            'expiring_within_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:365',
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
