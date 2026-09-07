<?php

namespace Modules\HRM\Application\Actions\EmployeeDocuments;

use Illuminate\Support\Facades\Storage;
use Modules\HRM\Application\DTOs\EmployeeDocuments\UpdateEmployeeDocumentData;
use Modules\HRM\Models\EmployeeDocument;
use Modules\Tenancy\Models\TenantStaff;

final class UpdateEmployeeDocumentAction
{
    public function execute(
        EmployeeDocument $document,
        UpdateEmployeeDocumentData $data,
    ): EmployeeDocument {
        abort_unless(
            $document->tenant_id === $data->tenantId,
            404,
        );

        $staff = TenantStaff::query()
            ->whereKey($data->tenantStaffId)
            ->where('tenant_id', $data->tenantId)
            ->where('employment_status', 'active')
            ->firstOrFail();

        $attributes = [
            'tenant_staff_id' => $staff->id,
            'type' => $data->type,
            'title' => $data->title,
            'document_number' => $data->documentNumber,
            'issue_date' => $data->issueDate,
            'expiry_date' => $data->expiryDate,
            'notes' => $data->notes,
            'is_active' => $data->isActive,
            'updated_by' => $data->updatedBy,
        ];

        if ($data->file !== null) {
            Storage::disk($document->file_disk)
                ->delete($document->file_path);

            $attributes['file_path'] = $data->file->store(
                "employee-documents/{$data->tenantId}/{$staff->id}",
                $document->file_disk,
            );

            $attributes['original_file_name'] =
                $data->file->getClientOriginalName();

            $attributes['mime_type'] =
                $data->file->getMimeType();

            $attributes['file_size'] =
                $data->file->getSize();
        }

        $document->update($attributes);

        return $document->refresh();
    }
}
