<?php

namespace Modules\Order\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Order\Services\CheckoutService;
use Modules\Order\Services\PricingEngine;

class OrderServiceProvider extends ServiceProvider
{
    protected string $name = 'Order';
    protected string $nameLower = 'order';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrations();
        $this->loadViews();
    }

    public function register(): void
    {
        $this->app->singleton(PricingEngine::class, function () {
            return new PricingEngine();
        });

        $this->app->singleton(CheckoutService::class, function ($app) {
            return new CheckoutService($app->make(PricingEngine::class));
        });

        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $configPath = __DIR__ . '/../Config/order.php';
        if (file_exists($configPath)) {
            $this->publishes([$configPath => config_path('order.php')], 'config');
            $this->mergeConfigFrom($configPath, 'order');
        }
    }

    protected function loadMigrations(): void
    {
        $migrationPath = __DIR__ . '/../database/migrations';
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    protected function loadViews(): void
    {
        $viewPath = __DIR__ . '/../resources/views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'order');
        }
    }
}
