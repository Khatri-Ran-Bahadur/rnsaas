<?php

namespace Modules\Tenancy\Application\Listeners;

use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Tenancy\Domain\Events\TenantMembershipStatusChanged;

final class RecordTenantMembershipChange
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantMembershipStatusChanged $event): void
    {
        $this->auditLogger->record(
            event: sprintf(
                'membership.%s',
                $event->newStatus->value,
            ),
            tenantId: $event->membership->tenant_id,
            actor: $event->changedBy,
            auditable: $event->membership,
            oldValues: [
                'status' => $event->oldStatus->value,
            ],
            newValues: [
                'status' => $event->newStatus->value,
            ],
            metadata: [
                'module' => 'tenancy',
                'membership_id' => $event->membership->id,
            ],
        );
    }
}
