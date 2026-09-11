<?php

namespace Modules\Accounting\Application\Actions\Attachments;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Modules\Accounting\Models\AccountingAttachment;

final class UploadAccountingAttachmentAction
{
    public function execute(
        Model $attachable,
        UploadedFile $file,
        CurrentTenant $currentTenant,
    ): AccountingAttachment {
        if (
            $attachable->getAttribute('tenant_id') !== $currentTenant->id()
        ) {
            abort(404);
        }

        $directory = sprintf(
            'accounting/%s/%s/%s',
            $currentTenant->id(),
            str_replace('\\', '_', $attachable::class),
            $attachable->getKey(),
        );

        $path = $file->store(
            $directory,
            'private',
        );

        return AccountingAttachment::query()->create([
            'tenant_id' => $currentTenant->id(),
            'attachable_type' => $attachable::class,
            'attachable_id' => $attachable->getKey(),
            'disk' => 'private',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
    }
}
