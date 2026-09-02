<?php

namespace Modules\Audit\Application\Services;

use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Audit\Domain\ValueObjects\AuditContext;
use Modules\Audit\Models\AuditLog;

final class DatabaseAuditLogger implements AuditLogger
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
    ): AuditLog {
        return AuditLog::query()->create([
            'tenant_id' => $tenantId,

            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),

            'event' => $event,

            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),

            'old_values' => $oldValues,
            'new_values' => $newValues,

            'metadata' => $metadata ?? $context?->metadata,

            'request_id' => $context?->requestId,
            'ip_address' => $context?->ipAddress,
            'user_agent' => $context?->userAgent,

            'created_at' => now(),
        ]);
    }
}
