<?php

namespace Modules\Accounting\Http\Resources\Attachments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountingAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_kb' => round($this->size / 1024, 2),
            'uploaded_at' => $this->created_at?->toISOString(),
        ];
    }
}
