<?php

namespace Modules\POS\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class PosServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'POS';

    protected string $nameLower = 'pos';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
