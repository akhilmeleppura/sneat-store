<?php

namespace Modules\Payment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Services\FinancialSettlementService;
use Modules\Payment\Services\PaymentManager;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'payment');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentManager::class, function ($app) {
            return new PaymentManager($app);
        });

        $this->app->singleton(FinancialSettlementService::class, function ($app) {
            return new FinancialSettlementService();
        });

        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            return $app->make(PaymentManager::class)->driver();
        });
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/payment.php' => config_path('payment.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__ . '/../Config/payment.php', 'payment'
        );
    }
}
