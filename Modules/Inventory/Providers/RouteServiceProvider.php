<?php

namespace Modules\Inventory\Providers;

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
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes(): void
    {
        $webRoutes = __DIR__ . '/../routes/web.php';
        if (file_exists($webRoutes)) {
            Route::middleware('web')->group($webRoutes);
        }
    }

    protected function mapApiRoutes(): void
    {
        $apiRoutes = __DIR__ . '/../routes/api.php';
        if (file_exists($apiRoutes)) {
            Route::prefix('api')->middleware('api')->group($apiRoutes);
        }
    }
}
