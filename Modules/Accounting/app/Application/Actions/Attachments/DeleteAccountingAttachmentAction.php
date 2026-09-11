<?php

namespace Modules\Accounting\Application\Actions\Attachments;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\Storage;
use Modules\Accounting\Models\AccountingAttachment;

final class DeleteAccountingAttachmentAction
{
    public function execute(
        AccountingAttachment $attachment,
        CurrentTenant $currentTenant,
    ): void {
        abort_unless(
            $attachment->tenant_id === $currentTenant->id(),
            404
        );

        Storage::disk($attachment->disk)->delete($attachment->path);

        $attachment->delete();
    }
}
