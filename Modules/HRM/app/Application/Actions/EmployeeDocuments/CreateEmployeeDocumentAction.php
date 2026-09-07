<?php

namespace Modules\HRM\Application\Actions\EmployeeDocuments;

use Modules\HRM\Application\DTOs\EmployeeDocuments\CreateEmployeeDocumentData;
use Modules\HRM\Models\EmployeeDocument;
use Modules\Tenancy\Models\TenantStaff;

final class CreateEmployeeDocumentAction
{
    public function execute(
        CreateEmployeeDocumentData $data,
    ): EmployeeDocument {
        $staff = TenantStaff::query()
            ->whereKey($data->tenantStaffId)
            ->where('tenant_id', $data->tenantId)
            ->where('employment_status', 'active')
            ->firstOrFail();

        $path = $data->file->store(
            "employee-documents/{$data->tenantId}/{$staff->id}",
            'private',
        );

        return EmployeeDocument::query()->create([
            'tenant_id' => $data->tenantId,
            'tenant_staff_id' => $staff->id,
            'type' => $data->type,
            'title' => $data->title,
            'document_number' => $data->documentNumber,
            'issue_date' => $data->issueDate,
            'expiry_date' => $data->expiryDate,
            'file_disk' => 'private',
            'file_path' => $path,
            'original_file_name' => $data->file->getClientOriginalName(),
            'mime_type' => $data->file->getMimeType(),
            'file_size' => $data->file->getSize(),
            'status' => 'pending',
            'notes' => $data->notes,
            'created_by' => $data->createdBy,
            'updated_by' => $data->createdBy,
            'is_active' => true,
        ]);
    }
}
