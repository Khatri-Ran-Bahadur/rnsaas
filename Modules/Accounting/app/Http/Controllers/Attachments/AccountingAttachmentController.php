<?php

namespace Modules\Accounting\Http\Controllers\Attachments;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Accounting\Application\Actions\Attachments\DeleteAccountingAttachmentAction;
use Modules\Accounting\Application\Actions\Attachments\UploadAccountingAttachmentAction;
use Modules\Accounting\Http\Requests\Attachments\UploadAccountingAttachmentRequest;
use Modules\Accounting\Http\Resources\Attachments\AccountingAttachmentResource;
use Modules\Accounting\Models\AccountingAttachment;
use Modules\Accounting\Models\CustomerPayment;
use Modules\Accounting\Models\SalesInvoice;

class AccountingAttachmentController extends Controller
{
    public function store(
        UploadAccountingAttachmentRequest $request,
        Model $attachable,
        UploadAccountingAttachmentAction $action,
        CurrentTenant $currentTenant,
    ): AccountingAttachmentResource {
        $attachment = $action->execute(
            attachable: $attachable,
            file: $request->file('file'),
            currentTenant: $currentTenant,
        );

        return new AccountingAttachmentResource($attachment);
    }

    public function storeInvoiceAttachment(
        UploadAccountingAttachmentRequest $request,
        SalesInvoice $invoice,
        UploadAccountingAttachmentAction $action,
        CurrentTenant $currentTenant,
    ): AccountingAttachmentResource {
        $attachment = $action->execute(
            attachable: $invoice,
            file: $request->file('file'),
            currentTenant: $currentTenant,
        );

        return new AccountingAttachmentResource($attachment);
    }

    public function storePaymentAttachment(
        UploadAccountingAttachmentRequest $request,
        CustomerPayment $payment,
        UploadAccountingAttachmentAction $action,
        CurrentTenant $currentTenant,
    ): AccountingAttachmentResource {
        $attachment = $action->execute(
            attachable: $payment,
            file: $request->file('file'),
            currentTenant: $currentTenant,
        );

        return new AccountingAttachmentResource($attachment);
    }

    public function download(
        AccountingAttachment $attachment,
        CurrentTenant $currentTenant,
    ) {
        abort_unless(
            $attachment->tenant_id === $currentTenant->id(),
            404
        );

        abort_unless(
            Storage::disk($attachment->disk)->exists($attachment->path),
            404
        );

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
            ],
        );
    }

    public function destroy(
        AccountingAttachment $attachment,
        DeleteAccountingAttachmentAction $action,
        CurrentTenant $currentTenant,
    ): JsonResponse {
        $action->execute(
            attachment: $attachment,
            currentTenant: $currentTenant,
        );

        return response()->json([
            'message' => 'Attachment deleted successfully.',
        ]);
    }
}
