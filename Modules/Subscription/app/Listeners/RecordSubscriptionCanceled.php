<?php

namespace Modules\Subscription\Listeners;

use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Audit\Domain\ValueObjects\AuditContext;
use Modules\Subscription\Events\TenantSubscriptionCanceled;

class RecordSubscriptionCanceled
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        TenantSubscriptionCanceled $event,
    ): void {
        $subscription = $event->subscription;

        $this->auditLogger->record(
            event: 'subscription.canceled',
            tenantId: $subscription->tenant_id,
            actor: auth()->user(),
            auditable: $subscription,
            oldValues: [
                'canceled_at' => null,
                'ends_at' => null,
            ],
            newValues: [
                'canceled_at' => $subscription->canceled_at?->toISOString(),
                'ends_at' => $subscription->ends_at?->toISOString(),
            ],
            metadata: [
                'module' => 'subscription',
                'source' => 'subscription.cancel',
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
