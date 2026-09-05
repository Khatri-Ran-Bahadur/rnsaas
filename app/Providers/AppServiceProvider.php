<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use App\Support\Tenancy\CurrentTenant;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentTenant::class, function (): CurrentTenant {
            return new CurrentTenant;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureFactories();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure Eloquent factory resolution for modular (nwidart) and application models.
     *
     * By default, Laravel expects all factories to reside in `Database\Factories\{ModelName}Factory`.
     * This resolver automatically maps modular models (e.g. `Modules\Subscription\Models\Plan`)
     * to their respective module factories (e.g. `Modules\Subscription\Database\Factories\PlanFactory`),
     * eliminating the need to define a manual `newFactory()` method on each model.
     */
    protected function configureFactories(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            // If the model belongs to a module (e.g., Modules\Subscription\Models\Plan)
            if (str_starts_with($modelName, 'Modules\\')) {
                $parts = explode('\\', $modelName);
                $moduleName = $parts[1] ?? '';
                $modelRelativeName = Str::after($modelName, "Modules\\{$moduleName}\\Models\\");

                return "Modules\\{$moduleName}\\Database\\Factories\\{$modelRelativeName}Factory";
            }

            // Fallback to standard Laravel factory resolution for app models
            $modelRelativeName = Str::startsWith($modelName, 'App\\Models\\')
                ? Str::after($modelName, 'App\\Models\\')
                : Str::after($modelName, 'App\\');

            return 'Database\\Factories\\'.$modelRelativeName.'Factory';
        });
    }
}
