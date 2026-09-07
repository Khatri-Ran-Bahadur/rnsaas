<?php

namespace Modules\HRM\Http\Requests\EmployeeDocuments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\HRM\Application\DTOs\EmployeeDocuments\CreateEmployeeDocumentData;
use Modules\HRM\Domain\Enums\EmployeeDocumentType;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee_documents.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_staff_id' => [
                'required',
                'integer',
            ],

            'type' => [
                'required',
                new Enum(EmployeeDocumentType::class),
            ],

            'title' => [
                'required',
                'string',
                'max:150',
            ],

            'document_number' => [
                'nullable',
                'string',
                'max:150',
            ],

            'issue_date' => [
                'nullable',
                'date',
            ],

            'expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:issue_date',
            ],

            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function toData(
        int $tenantId,
        int $createdBy,
    ): CreateEmployeeDocumentData {
        return new CreateEmployeeDocumentData(
            tenantId: $tenantId,
            tenantStaffId: (int) $this->integer('tenant_staff_id'),
            type: EmployeeDocumentType::from($this->string('type')->toString()),
            title: $this->string('title')->toString(),
            documentNumber: $this->input('document_number'),
            issueDate: $this->input('issue_date'),
            expiryDate: $this->input('expiry_date'),
            file: $this->file('file'),
            notes: $this->input('notes'),
            createdBy: $createdBy,
        );
    }
}
