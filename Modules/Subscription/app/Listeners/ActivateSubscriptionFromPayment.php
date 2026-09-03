<?php

namespace Modules\Subscription\Listeners;

use Modules\Payment\Events\PaymentPaid;
use Modules\Subscription\Actions\ActivateSubscriptionFromPaymentAction;

class ActivateSubscriptionFromPayment
{
    public function __construct(
        private readonly ActivateSubscriptionFromPaymentAction $activateSubscription,
    ) {}

    public function handle(PaymentPaid $event): void
    {
        $this->activateSubscription->handle(
            $event->payment,
        );
    }
}