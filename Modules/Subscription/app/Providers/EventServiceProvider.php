<?php

namespace Modules\Subscription\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Payment\Events\PaymentPaid;
use Modules\Subscription\Events\SubscriptionActivated;
use Modules\Subscription\Events\SubscriptionReactivated;
use Modules\Subscription\Events\TenantSubscriptionCanceled;
use Modules\Subscription\Events\TenantSubscriptionCreated;
use Modules\Subscription\Events\TenantSubscriptionExpired;
use Modules\Subscription\Listeners\ActivateSubscriptionFromPayment;
use Modules\Subscription\Listeners\RecordSubscriptionActivated;
use Modules\Subscription\Listeners\RecordSubscriptionCanceled;
use Modules\Subscription\Listeners\RecordSubscriptionCreated;
use Modules\Subscription\Listeners\RecordSubscriptionExpired;
use Modules\Subscription\Listeners\RecordSubscriptionReactivated;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        PaymentPaid::class => [
            ActivateSubscriptionFromPayment::class,
        ],
        TenantSubscriptionCreated::class => [
            RecordSubscriptionCreated::class,
        ],
        TenantSubscriptionCanceled::class => [
            RecordSubscriptionCanceled::class,
        ],
        TenantSubscriptionExpired::class => [
            RecordSubscriptionExpired::class,
        ],
        SubscriptionActivated::class => [
            RecordSubscriptionActivated::class,
        ],
        SubscriptionReactivated::class => [
            RecordSubscriptionReactivated::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
