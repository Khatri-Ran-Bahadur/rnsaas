<?php

namespace Modules\HRM\Application\Actions\EmployeeDocuments;

use Illuminate\Support\Facades\Storage;
use Modules\HRM\Models\EmployeeDocument;

final class DeleteEmployeeDocumentAction
{
    public function execute(
        EmployeeDocument $document,
        int $tenantId,
    ): void {
        abort_unless(
            $document->tenant_id === $tenantId,
            404,
        );

        Storage::disk($document->file_disk)
            ->delete($document->file_path);

        $document->delete();
    }
}
