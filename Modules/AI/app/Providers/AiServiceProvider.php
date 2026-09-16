<?php

namespace Modules\AI\Providers;

use Modules\AI\Services\AiAgentService;
use Modules\AI\Services\ModuleToolRegistry;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AiServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'AI';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'ai';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(ModuleToolRegistry::class, function () {
            return new ModuleToolRegistry;
        });

        $this->app->singleton(AiAgentService::class, function ($app) {
            return new AiAgentService($app->make(ModuleToolRegistry::class));
        });
    }
}
