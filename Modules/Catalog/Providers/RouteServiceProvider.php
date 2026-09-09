<?php

namespace Modules\Catalog\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Called before routes are registered.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    /**
     * Define the "web" routes for the module.
     */
    protected function mapWebRoutes(): void
    {
        $webRoutes = __DIR__ . '/../routes/web.php';
        if (file_exists($webRoutes)) {
            Route::middleware('web')
                ->group($webRoutes);
        }
    }

    /**
     * Define the "api" routes for the module.
     */
    protected function mapApiRoutes(): void
    {
        $apiRoutes = __DIR__ . '/../routes/api.php';
        if (file_exists($apiRoutes)) {
            Route::prefix('api')
                ->middleware('api')
                ->group($apiRoutes);
        }
    }
}
