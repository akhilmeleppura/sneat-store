<?php

namespace Modules\Catalog\Providers;

use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
{
    protected string $name = 'Catalog';
    protected string $nameLower = 'catalog';

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
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = __DIR__ . '/../Config/catalog.php';
        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path('catalog.php'),
            ], 'config');

            $this->mergeConfigFrom($configPath, 'catalog');
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
            $this->loadViewsFrom($viewPath, 'catalog');
        }
    }
}
