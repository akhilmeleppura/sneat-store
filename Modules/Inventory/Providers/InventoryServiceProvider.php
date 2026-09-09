<?php

namespace Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Services\InventoryService;

class InventoryServiceProvider extends ServiceProvider
{
    protected string $name = 'Inventory';
    protected string $nameLower = 'inventory';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrations();
        $this->loadViews();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(InventoryService::class, function () {
            return new InventoryService();
        });

        $this->app->alias(InventoryService::class, 'inventory');
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = __DIR__ . '/../Config/inventory.php';
        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path('inventory.php'),
            ], 'config');

            $this->mergeConfigFrom($configPath, 'inventory');
        }
    }

    /**
     * Load migrations.
     */
    protected function loadMigrations(): void
    {
        $migrationPath = __DIR__ . '/../database/migrations';
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * Load views.
     */
    protected function loadViews(): void
    {
        $viewPath = __DIR__ . '/../resources/views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'inventory');
        }
    }
}
