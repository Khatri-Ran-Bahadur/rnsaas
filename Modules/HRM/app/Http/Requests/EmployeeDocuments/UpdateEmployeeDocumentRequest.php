<?php

namespace Modules\HRM\Http\Requests\EmployeeDocuments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\HRM\Application\DTOs\EmployeeDocuments\UpdateEmployeeDocumentData;
use Modules\HRM\Domain\Enums\EmployeeDocumentType;

class UpdateEmployeeDocumentRequest extends FormRequest
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
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function toData(
        int $tenantId,
        int $updatedBy,
    ): UpdateEmployeeDocumentData {
        return new UpdateEmployeeDocumentData(
            tenantId: $tenantId,
            tenantStaffId: (int) $this->integer('tenant_staff_id'),
            type: EmployeeDocumentType::from($this->string('type')->toString()),
            title: $this->string('title')->toString(),
            documentNumber: $this->input('document_number'),
            issueDate: $this->input('issue_date'),
            expiryDate: $this->input('expiry_date'),
            file: $this->file('file'),
            notes: $this->input('notes'),
            isActive: $this->boolean('is_active'),
            updatedBy: $updatedBy,
        );
    }
}
