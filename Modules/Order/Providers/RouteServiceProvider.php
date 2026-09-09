<?php

namespace Modules\Order\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        $webRoutes = __DIR__ . '/../routes/web.php';
        if (file_exists($webRoutes)) {
            Route::middleware('web')->group($webRoutes);
        }
    }
}
