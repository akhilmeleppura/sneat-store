<?php

namespace Modules\Context\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Context\Services\ContextService;
use Modules\Context\Services\CurrencyService;

class ContextServiceProvider extends ServiceProvider
{
    protected string $name = 'Context';
    protected string $nameLower = 'context';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Load global currency helper functions
        $helperPath = __DIR__ . '/../Helpers/CurrencyHelper.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }

        // Bind the ContextService as a singleton in Laravel's service container
        $this->app->singleton('context', function ($app) {
            return new ContextService();
        });

        $this->app->alias('context', ContextService::class);

        // Bind CurrencyService singleton
        $this->app->singleton(CurrencyService::class, function ($app) {
            return new CurrencyService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Config
        $configPath = __DIR__ . '/../Config/context.php';
        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path('context.php'),
            ], 'config');

            $this->mergeConfigFrom($configPath, 'context');
        }

        // Load Migrations
        $migrationPath = __DIR__ . '/../database/migrations';
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        // Load Views
        $viewPath = __DIR__ . '/../resources/views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'context');
        }

        // Register Blade Directives
        \Illuminate\Support\Facades\Blade::directive('money', function ($expression) {
            return "<?php echo money($expression); ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('currency', function ($expression) {
            return "<?php echo money($expression); ?>";
        });

        // Load Routes
        $routePath = __DIR__ . '/../routes/web.php';
        if (file_exists($routePath)) {
            \Illuminate\Support\Facades\Route::middleware('web')->group($routePath);
        }
    }
}
