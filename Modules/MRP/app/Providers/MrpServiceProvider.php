<?php

namespace Modules\MRP\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class MrpServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'MRP';

    protected string $nameLower = 'mrp';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
