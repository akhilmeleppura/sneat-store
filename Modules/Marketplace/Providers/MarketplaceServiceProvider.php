<?php

namespace Modules\Marketplace\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Marketplace\Services\CommissionService;

class MarketplaceServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'marketplace');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(CommissionService::class, function ($app) {
            return new CommissionService();
        });
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/marketplace.php' => config_path('marketplace.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__ . '/../Config/marketplace.php', 'marketplace'
        );
    }
}
