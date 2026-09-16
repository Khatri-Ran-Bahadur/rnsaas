<?php

namespace Modules\Tax\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Tax';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        $path = module_path($this->name, '/routes/web.php');
        if (file_exists($path)) {
            Route::middleware('web')->group($path);
        }
    }
}
