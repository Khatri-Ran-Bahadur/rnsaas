<?php

namespace Modules\Audit\Domain\Contracts;

use Modules\Audit\Domain\ValueObjects\AuditContext;
use Modules\Audit\Models\AuditLog;

interface AuditLogger
{
    public function record(
        string $event,
        ?int $tenantId = null,
        ?object $actor = null,
        ?object $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?AuditContext $context = null,
    ): AuditLog;
}
