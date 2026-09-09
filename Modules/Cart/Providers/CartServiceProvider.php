<?php

namespace Modules\Cart\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Cart\Services\CartService;

class CartServiceProvider extends ServiceProvider
{
    protected string $name = 'Cart';
    protected string $nameLower = 'cart';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrations();
        $this->loadViews();
    }

    public function register(): void
    {
        $this->app->singleton(CartService::class, function ($app) {
            return new CartService();
        });

        $this->app->singleton(\Modules\Cart\Services\AbandonedCartService::class, function ($app) {
            return new \Modules\Cart\Services\AbandonedCartService();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Cart\Console\DetectAbandonedCartsCommand::class,
            ]);
        }

        $this->app->alias(CartService::class, 'cart');
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $configPath = __DIR__ . '/../Config/cart.php';
        if (file_exists($configPath)) {
            $this->publishes([$configPath => config_path('cart.php')], 'config');
            $this->mergeConfigFrom($configPath, 'cart');
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
            $this->loadViewsFrom($viewPath, 'cart');
        }
    }
}
