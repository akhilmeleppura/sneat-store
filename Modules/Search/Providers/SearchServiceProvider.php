<?php

namespace Modules\Search\Providers;

use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // No bindings needed for now
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load module routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        // The Product model already uses the Searchable trait.
    }
}
