<?php

namespace Modules\Subscription\Listeners;

use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Audit\Domain\ValueObjects\AuditContext;
use Modules\Subscription\Events\SubscriptionActivated;

class RecordSubscriptionActivated
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(SubscriptionActivated $event): void
    {
        $subscription = $event->subscription;

        $this->auditLogger->record(
            event: 'subscription.activated',
            tenantId: $subscription->tenant_id,
            actor: auth()->user(),
            auditable: $subscription,
            oldValues: [
                'status' => $event->previousStatus,
            ],
            newValues: [
                'status' => $subscription->status->value,
                'starts_at' => $subscription->starts_at?->toISOString(),
                'current_period_starts_at' => $subscription
                    ->current_period_starts_at
                    ?->toISOString(),
            ],
            metadata: [
                'module' => 'subscription',
                'source' => 'subscription.activate',
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
