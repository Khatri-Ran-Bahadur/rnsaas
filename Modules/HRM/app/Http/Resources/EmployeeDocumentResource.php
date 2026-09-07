<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDocumentResource extends JsonResource
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

            'type' => [
                'value' => $this->type?->value,
                'label' => $this->type?->label(),
            ],

            'title' => $this->title,
            'document_number' => $this->document_number,

            'issue_date' => $this->issue_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),

            'file' => [
                'name' => $this->original_file_name,
                'mime_type' => $this->mime_type,
                'size' => $this->file_size,
                'download_url' => route('admin.hrm.employee-documents.download', $this->resource),
            ],

            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],

            'notes' => $this->notes,

            'is_active' => $this->is_active,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,

            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at?->toISOString(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
