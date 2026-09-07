<?php

namespace Modules\HRM\Application\DTOs\EmployeeDocuments;

use Illuminate\Http\UploadedFile;
use Modules\HRM\Domain\Enums\EmployeeDocumentType;

final readonly class UpdateEmployeeDocumentData
{
    public function __construct(
        public int $tenantId,
        public int $tenantStaffId,
        public EmployeeDocumentType $type,
        public string $title,
        public ?string $documentNumber,
        public ?string $issueDate,
        public ?string $expiryDate,
        public ?UploadedFile $file,
        public ?string $notes,
        public bool $isActive,
        public int $updatedBy,
    ) {}
}
