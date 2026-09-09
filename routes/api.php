<?php

use App\Http\Controllers\Api\V1\ApiStorefrontController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Sneat Storefront Headless REST API (v1)
|--------------------------------------------------------------------------
| Clean, predictable JSON endpoints for mobile apps (Flutter, React Native)
| and headless frontends (Next.js, Nuxt, Astro).
*/
Route::prefix('v1')->group(function () {
    // OpenAPI 3.0 Documentation Specification
    Route::get('/openapi.json', [\App\Http\Controllers\Api\OpenApiController::class, 'spec'])->name('api.v1.openapi');

    // Store Metadata & Currencies
    Route::get('/store/info', [ApiStorefrontController::class, 'storeInfo'])->name('api.v1.store.info');

    // Catalog & Discovery
    Route::get('/store/products', [ApiStorefrontController::class, 'products'])->name('api.v1.products');
    Route::get('/store/products/{slug}', [ApiStorefrontController::class, 'product'])->name('api.v1.product.show');
    Route::get('/store/categories', [ApiStorefrontController::class, 'categories'])->name('api.v1.categories');
    Route::get('/store/brands', [ApiStorefrontController::class, 'brands'])->name('api.v1.brands');
    Route::get('/store/search/autocomplete', [\Modules\Catalog\Http\Controllers\Store\SearchController::class, 'autocomplete'])->name('api.v1.search.autocomplete');
    Route::get('/store/search', [\Modules\Catalog\Http\Controllers\Store\SearchController::class, 'search'])->name('api.v1.search');

    // Cart Management
    Route::get('/store/cart', [ApiStorefrontController::class, 'cart'])->name('api.v1.cart');
    Route::post('/store/cart/add', [ApiStorefrontController::class, 'addToCart'])->name('api.v1.cart.add');

    // Public Order Tracking
    Route::post('/store/orders/track', [ApiStorefrontController::class, 'trackOrder'])->name('api.v1.orders.track');
});
