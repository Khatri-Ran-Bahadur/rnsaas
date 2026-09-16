<?php

namespace Modules\Tax\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class TaxServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tax';

    protected string $nameLower = 'tax';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
