<?php

namespace Modules\Tenancy\Providers;

use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\ServiceProvider;
use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;

class TenancyAuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrganizationAuthorizationService::class);
    }

    public function boot(): void
    {
        $registerGateHook = function (GateContract $gate): void {
            $gate->before(function ($user, string $ability, array $args = []): ?bool {
                if (! $user instanceof User) {
                    return null;
                }

                $authService = app(OrganizationAuthorizationService::class);

                if ($authService->isOrganizationAbility($ability)) {
                    return $authService->allows($user, $ability);
                }

                return null;
            });
        };

        if ($this->app->resolved(GateContract::class)) {
            $registerGateHook($this->app->make(GateContract::class));
        } else {
            $this->app->resolving(GateContract::class, $registerGateHook);
        }
    }
}
