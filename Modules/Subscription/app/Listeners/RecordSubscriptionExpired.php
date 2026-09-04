<?php

namespace Modules\Subscription\Listeners;

use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Audit\Domain\ValueObjects\AuditContext;
use Modules\Subscription\Events\TenantSubscriptionExpired;

class RecordSubscriptionExpired
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        TenantSubscriptionExpired $event,
    ): void {
        $subscription = $event->subscription;

        $this->auditLogger->record(
            event: 'subscription.expired',
            tenantId: $subscription->tenant_id,
            actor: auth()->user(),
            auditable: $subscription,
            oldValues: [
                'status' => $event->subscription->getOriginal('status')?->value
                    ?? $event->subscription->getRawOriginal('status'),
            ],
            newValues: [
                'status' => $subscription->status->value,
                'ends_at' => $subscription->ends_at?->toISOString(),
            ],
            metadata: [
                'module' => 'subscription',
                'source' => 'subscription.expire',
                'subscription_public_id' => $subscription->public_id,
            ],
            context: new AuditContext(
                requestId: request()->header('X-Request-ID'),
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            ),
        );
    }
}
