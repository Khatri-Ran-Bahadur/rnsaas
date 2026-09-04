<?php

namespace Modules\Subscription\Listeners;

use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Audit\Domain\ValueObjects\AuditContext;
use Modules\Subscription\Events\SubscriptionReactivated;

class RecordSubscriptionReactivated
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        SubscriptionReactivated $event,
    ): void {
        $subscription = $event->subscription;

        $this->auditLogger->record(
            event: 'subscription.reactivated',
            tenantId: $subscription->tenant_id,
            actor: auth()->user(),
            auditable: $subscription,
            oldValues: [
                'canceled_at' => true,
                'ends_at' => true,
            ],
            newValues: [
                'canceled_at' => null,
                'ends_at' => null,
            ],
            metadata: [
                'module' => 'subscription',
                'source' => 'subscription.reactivate',
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
