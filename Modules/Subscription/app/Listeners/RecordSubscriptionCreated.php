<?php

namespace Modules\Subscription\Listeners;

use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Audit\Domain\ValueObjects\AuditContext;
use Modules\Subscription\Events\TenantSubscriptionCreated;

class RecordSubscriptionCreated
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        TenantSubscriptionCreated $event,
    ): void {
        $subscription = $event->subscription;

        $this->auditLogger->record(
            event: 'subscription.created',
            tenantId: $subscription->tenant_id,
            actor: auth()->user(),
            auditable: $subscription,
            oldValues: null,
            newValues: [
                'plan_id' => $subscription->plan_id,
                'status' => $subscription->status->value,
                'starts_at' => $subscription->starts_at?->toISOString(),
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'current_period_starts_at' => $subscription->current_period_starts_at?->toISOString(),
                'current_period_ends_at' => $subscription->current_period_ends_at?->toISOString(),
            ],
            metadata: [
                'module' => 'subscription',
                'source' => 'subscription.create',
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
