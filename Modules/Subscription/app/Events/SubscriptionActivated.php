<?php

namespace Modules\Subscription\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Subscription\Models\TenantSubscription;

class SubscriptionActivated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TenantSubscription $subscription,
        public string $previousStatus = 'pending',
    ) {}
}
