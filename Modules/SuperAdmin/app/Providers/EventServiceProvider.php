<?php

namespace Modules\SuperAdmin\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\SuperAdmin\Listeners\SecurityAuthSubscriber;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Event subscribers for the module.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        SecurityAuthSubscriber::class,
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
