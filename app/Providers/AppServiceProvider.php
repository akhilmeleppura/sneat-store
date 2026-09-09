<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
                ];
            }
            return [];
        });

        // Register E-Commerce Lifecycle & Marketplace Notification Listeners
        \Illuminate\Support\Facades\Event::listen(
            \Modules\Order\Events\OrderPlacedEvent::class,
            [\Modules\Order\Listeners\SendOrderPlacedNotifications::class, 'handle']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Payment\Events\OrderPaymentSettledEvent::class,
            [\Modules\Payment\Listeners\SendOrderSettledNotifications::class, 'handle']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Inventory\Events\LowStockAlertEvent::class,
            [\Modules\Inventory\Listeners\SendLowStockNotification::class, 'handle']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Marketplace\Events\VendorPayoutRequestedEvent::class,
            [\Modules\Marketplace\Listeners\SendVendorPayoutNotification::class, 'handleRequested']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Marketplace\Events\VendorPayoutApprovedEvent::class,
            [\Modules\Marketplace\Listeners\SendVendorPayoutNotification::class, 'handleApproved']
        );
    }
}
