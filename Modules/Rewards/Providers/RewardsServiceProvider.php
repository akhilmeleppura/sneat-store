<?php

namespace Modules\Rewards\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Rewards\Services\LoyaltyService;

class RewardsServiceProvider extends ServiceProvider
{
    protected string $name = 'Rewards';
    protected string $nameLower = 'rewards';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrations();
        $this->loadViews();
        $this->loadRoutes();
    }

    public function register(): void
    {
        $this->app->singleton(LoyaltyService::class, function () {
            return new LoyaltyService();
        });
    }

    protected function registerConfig(): void
    {
        $configPath = __DIR__ . '/../Config/rewards.php';
        if (file_exists($configPath)) {
            $this->publishes([$configPath => config_path('rewards.php')], 'config');
            $this->mergeConfigFrom($configPath, 'rewards');
        }
    }

    protected function loadMigrations(): void
    {
        $migrationPath = __DIR__ . '/../Database/migrations';
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    protected function loadViews(): void
    {
        $viewPath = __DIR__ . '/../resources/views';
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'rewards');
        }
    }

    protected function loadRoutes(): void
    {
        $webRoutes = __DIR__ . '/../routes/web.php';
        if (file_exists($webRoutes)) {
            $this->loadRoutesFrom($webRoutes);
        }

        $apiRoutes = __DIR__ . '/../routes/api.php';
        if (file_exists($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }
    }
}
