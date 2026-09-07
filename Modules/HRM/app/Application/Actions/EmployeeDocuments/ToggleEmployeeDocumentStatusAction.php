<?php

namespace Modules\HRM\Application\Actions\EmployeeDocuments;

use Modules\HRM\Models\EmployeeDocument;

final class ToggleEmployeeDocumentStatusAction
{
    public function execute(
        EmployeeDocument $document,
        int $tenantId,
        int $updatedBy,
    ): EmployeeDocument {
        abort_unless(
            $document->tenant_id === $tenantId,
            404,
        );

        $document->update([
            'is_active' => ! $document->is_active,
            'updated_by' => $updatedBy,
        ]);

        return $document->refresh();
    }
}
