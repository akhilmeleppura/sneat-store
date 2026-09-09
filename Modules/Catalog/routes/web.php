<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\BrandController;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Catalog\Http\Controllers\ProductController;
use Modules\Catalog\Http\Controllers\Store\StorefrontController;

/*
|--------------------------------------------------------------------------
| Public Storefront Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/', [StorefrontController::class, 'home'])->name('storefront.home');
    Route::get('/store', [StorefrontController::class, 'home'])->name('storefront.store');
    Route::get('/store/products', [StorefrontController::class, 'catalog'])->name('storefront.catalog');
    Route::get('/store/products/{slug}', [StorefrontController::class, 'productDetail'])->name('storefront.product.show');
    Route::post('/store/products/{slug}/reviews', [\Modules\Catalog\Http\Controllers\Store\StorefrontReviewController::class, 'store'])
        ->middleware('auth')
        ->name('storefront.reviews.store');

    // Wishlist Toggle (AJAX / Form)
    Route::post('/store/wishlist/toggle', [\Modules\Catalog\Http\Controllers\Store\WishlistController::class, 'toggle'])->name('store.wishlist.toggle');
    Route::post('/account/wishlist/toggle', [\Modules\Catalog\Http\Controllers\Store\WishlistController::class, 'toggle'])->name('account.wishlist.toggle');

    // Search & Autocomplete
    Route::get('/store/search', [\Modules\Catalog\Http\Controllers\Store\SearchController::class, 'search'])->name('store.search');
    Route::get('/store/search/autocomplete', [\Modules\Catalog\Http\Controllers\Store\SearchController::class, 'autocomplete'])->name('store.search.autocomplete');

    // Product Comparison Tool
    Route::get('/store/compare', [\Modules\Catalog\Http\Controllers\Store\ProductCompareController::class, 'index'])->name('store.compare.index');
    Route::post('/store/compare/add', [\Modules\Catalog\Http\Controllers\Store\ProductCompareController::class, 'add'])->name('store.compare.add');
    Route::post('/store/compare/remove', [\Modules\Catalog\Http\Controllers\Store\ProductCompareController::class, 'remove'])->name('store.compare.remove');
    Route::post('/store/compare/clear', [\Modules\Catalog\Http\Controllers\Store\ProductCompareController::class, 'clear'])->name('store.compare.clear');

    // Back in Stock Notifications
    Route::post('/store/back-in-stock/subscribe', [\Modules\Catalog\Http\Controllers\Store\BackInStockController::class, 'subscribe'])->name('store.back_in_stock.subscribe');
});

// Authenticated Customer Wishlist Portal Routes
Route::middleware(['web', 'auth', 'tenant.context'])->group(function () {
    Route::get('/account/wishlist', [\Modules\Catalog\Http\Controllers\Store\WishlistController::class, 'index'])->name('account.wishlist');
    Route::post('/account/wishlist/{id}/move-to-cart', [\Modules\Catalog\Http\Controllers\Store\WishlistController::class, 'moveToCart'])->name('account.wishlist.move_to_cart');
    Route::delete('/account/wishlist/{id}', [\Modules\Catalog\Http\Controllers\Store\WishlistController::class, 'destroy'])->name('account.wishlist.destroy');
});


/*
|--------------------------------------------------------------------------
| Admin Catalog Routes
|--------------------------------------------------------------------------
*/
Route::prefix('catalog')->name('catalog.')->middleware(['web', 'auth', 'tenant.context'])->group(function () {
    // Products
    Route::resource('products', ProductController::class);

    // Reviews & Moderation
    Route::get('reviews', [\Modules\Catalog\Http\Controllers\Admin\AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('reviews/{id}/toggle', [\Modules\Catalog\Http\Controllers\Admin\AdminReviewController::class, 'toggleApproval'])->name('reviews.toggle');
    Route::post('reviews/{id}/reply', [\Modules\Catalog\Http\Controllers\Admin\AdminReviewController::class, 'reply'])->name('reviews.reply');
    Route::delete('reviews/{id}', [\Modules\Catalog\Http\Controllers\Admin\AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Categories
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Brands
    Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
    Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
    Route::delete('brands/{id}', [BrandController::class, 'destroy'])->name('brands.destroy');

    // Bulk Export
    Route::get('export-csv', [\Modules\Catalog\Http\Controllers\Admin\BulkCatalogController::class, 'exportCsv'])->name('export_csv');

    // Back-in-Stock Alerts & Restock Radar
    Route::get('back-in-stock', [\Modules\Catalog\Http\Controllers\Admin\AdminBackInStockController::class, 'index'])->name('back_in_stock.index');
    Route::post('back-in-stock/{productId}/notify', [\Modules\Catalog\Http\Controllers\Admin\AdminBackInStockController::class, 'notify'])->name('back_in_stock.notify');
});

// Admin shortcut alias
Route::middleware(['web', 'auth', 'tenant.context'])->group(function () {
    Route::get('admin/back-in-stock', [\Modules\Catalog\Http\Controllers\Admin\AdminBackInStockController::class, 'index'])->name('admin.back_in_stock.index');
    Route::post('admin/back-in-stock/{productId}/notify', [\Modules\Catalog\Http\Controllers\Admin\AdminBackInStockController::class, 'notify'])->name('admin.back_in_stock.notify');
});

