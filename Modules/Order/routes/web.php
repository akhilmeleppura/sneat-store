<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\Admin\OrderController;
use Modules\Order\Http\Controllers\CheckoutController;
use Modules\Order\Http\Controllers\Customer\CustomerPortalController;

// Public Order Tracking and Invoice Routes
Route::middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/track-order', [\Modules\Order\Http\Controllers\PublicOrderTrackingController::class, 'index'])->name('order.track.page');
    Route::post('/track-order', [\Modules\Order\Http\Controllers\PublicOrderTrackingController::class, 'track'])->name('order.track.search');
    Route::get('/orders/{orderNumber}/invoice', [CustomerPortalController::class, 'invoice'])->name('orders.invoice.public');
    Route::get('/gift-cards', function () {
        return view('order::storefront.gift_cards');
    })->name('gift_cards.balance_page');
});

// Storefront Checkout Routes
Route::prefix('store')->name('store.')->middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping'])->name('checkout.calculate_shipping');
    Route::post('/checkout/place', [CheckoutController::class, 'placeOrder'])->name('checkout.place');
    Route::get('/order/confirmation/{orderNumber}', [CheckoutController::class, 'confirmation'])->name('order.confirmation');
    Route::post('/gift-cards/check', [\Modules\Order\Http\Controllers\GiftCardController::class, 'check'])->name('gift_cards.check');
});

// Customer Account Portal Routes
Route::prefix('account')->name('account.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/dashboard', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [CustomerPortalController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{orderNumber}', [CustomerPortalController::class, 'showOrder'])->name('orders.show');
    Route::get('/orders/{orderNumber}/invoice', [CustomerPortalController::class, 'invoice'])->name('orders.invoice');
    Route::post('/orders/{orderNumber}/cancel', [CustomerPortalController::class, 'requestCancellation'])->name('orders.cancel');
    Route::post('/orders/{orderNumber}/return', [CustomerPortalController::class, 'requestReturn'])->name('orders.return');
    Route::get('/profile', [CustomerPortalController::class, 'profile'])->name('profile');
    Route::post('/profile', [CustomerPortalController::class, 'updateProfile'])->name('profile.update');
    Route::get('/privacy/export-data', [\Modules\Order\Http\Controllers\Customer\GdprController::class, 'exportData'])->name('privacy.export');
    Route::post('/privacy/request-deletion', [\Modules\Order\Http\Controllers\Customer\GdprController::class, 'requestDeletion'])->name('privacy.deletion');
    Route::get('/rma', [\Modules\Order\Http\Controllers\Customer\CustomerRmaController::class, 'index'])->name('rma.index');
    Route::post('/rma', [\Modules\Order\Http\Controllers\Customer\CustomerRmaController::class, 'store'])->name('rma.store');
    Route::post('/rma/request', [\Modules\Order\Http\Controllers\RmaController::class, 'request'])->name('rma.request');
    Route::get('/loyalty', [CustomerPortalController::class, 'loyalty'])->name('loyalty');
    Route::get('/referrals', [\Modules\Order\Http\Controllers\Customer\CustomerAffiliateController::class, 'index'])->name('referrals.index');
    Route::post('/referrals/payout', [\Modules\Order\Http\Controllers\Customer\CustomerAffiliateController::class, 'updatePayout'])->name('referrals.payout');
});

// B2B Wholesale Request for Quote (RFQ)
Route::middleware(['web', 'tenant.context'])->group(function () {
    Route::post('/b2b/rfq', [\Modules\Order\Http\Controllers\RfqController::class, 'store'])->name('b2b.rfq.store');
    Route::get('/b2b/rfq/{quoteNumber}', [\Modules\Order\Http\Controllers\RfqController::class, 'show'])->name('b2b.rfq.show');
});

// Sneat Admin RMA Management Routes
Route::prefix('admin/rma')->name('admin.rma.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [\Modules\Order\Http\Controllers\Admin\AdminRmaController::class, 'index'])->name('index');
    Route::post('/{id}/status', [\Modules\Order\Http\Controllers\Admin\AdminRmaController::class, 'updateStatus'])->name('status');
});

// Sneat Admin B2B Quotes (RFQ) Routes
Route::prefix('admin/rfq')->name('admin.rfq.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [\Modules\Order\Http\Controllers\Admin\AdminRfqController::class, 'index'])->name('index');
    Route::get('/{id}', [\Modules\Order\Http\Controllers\Admin\AdminRfqController::class, 'show'])->name('show');
    Route::post('/{id}/proposal', [\Modules\Order\Http\Controllers\Admin\AdminRfqController::class, 'submitProposal'])->name('proposal');
    Route::post('/{id}/status', [\Modules\Order\Http\Controllers\Admin\AdminRfqController::class, 'updateStatus'])->name('status');
});

// Sneat Admin Gift Card Management Routes
Route::prefix('admin/gift-cards')->name('admin.gift_cards.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [\Modules\Order\Http\Controllers\Admin\AdminGiftCardController::class, 'index'])->name('index');
    Route::post('/', [\Modules\Order\Http\Controllers\Admin\AdminGiftCardController::class, 'store'])->name('store');
    Route::post('/{id}/toggle', [\Modules\Order\Http\Controllers\Admin\AdminGiftCardController::class, 'toggle'])->name('toggle');
});

// Sneat Admin Tax Rates & VAT Configuration Routes
Route::prefix('admin/taxes')->name('admin.taxes.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [\Modules\Order\Http\Controllers\Admin\AdminTaxRateController::class, 'index'])->name('index');
    Route::post('/', [\Modules\Order\Http\Controllers\Admin\AdminTaxRateController::class, 'store'])->name('store');
    Route::put('/{id}', [\Modules\Order\Http\Controllers\Admin\AdminTaxRateController::class, 'update'])->name('update');
    Route::post('/{id}/toggle', [\Modules\Order\Http\Controllers\Admin\AdminTaxRateController::class, 'toggle'])->name('toggle');
    Route::delete('/{id}', [\Modules\Order\Http\Controllers\Admin\AdminTaxRateController::class, 'destroy'])->name('destroy');
});

// Sneat Admin Order Management Routes
Route::prefix('admin/orders')->name('admin.orders.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{id}', [OrderController::class, 'show'])->name('show');
    Route::post('/{id}/status', [OrderController::class, 'updateStatus'])->name('status');
});

// Sneat Admin Analytics & Reports Routes
Route::prefix('admin/reports')->name('admin.reports.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/sales', [\Modules\Order\Http\Controllers\Admin\AnalyticsReportController::class, 'sales'])->name('sales');
    Route::get('/vendors', [\Modules\Order\Http\Controllers\Admin\AnalyticsReportController::class, 'vendors'])->name('vendors');
    Route::get('/inventory', [\Modules\Order\Http\Controllers\Admin\AnalyticsReportController::class, 'inventory'])->name('inventory');
    Route::get('/export/sales', [\Modules\Order\Http\Controllers\Admin\AnalyticsReportController::class, 'exportSalesCsv'])->name('export.sales');
});

// Sneat Admin Coupons & Promotions Routes
Route::prefix('admin/coupons')->name('admin.coupons.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [\Modules\Order\Http\Controllers\Admin\AdminCouponController::class, 'index'])->name('index');
    Route::get('/create', [\Modules\Order\Http\Controllers\Admin\AdminCouponController::class, 'create'])->name('create');
    Route::post('/', [\Modules\Order\Http\Controllers\Admin\AdminCouponController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [\Modules\Order\Http\Controllers\Admin\AdminCouponController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\Modules\Order\Http\Controllers\Admin\AdminCouponController::class, 'update'])->name('update');
    Route::post('/{id}/toggle', [\Modules\Order\Http\Controllers\Admin\AdminCouponController::class, 'toggleStatus'])->name('toggle');
    Route::delete('/{id}', [\Modules\Order\Http\Controllers\Admin\AdminCouponController::class, 'destroy'])->name('destroy');
});

// Sneat Admin Shipping Carriers & Methods Routes
Route::prefix('admin/shipping-methods')->name('admin.shipping-methods.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [\Modules\Order\Http\Controllers\Admin\ShippingMethodController::class, 'index'])->name('index');
    Route::get('/create', [\Modules\Order\Http\Controllers\Admin\ShippingMethodController::class, 'create'])->name('create');
    Route::post('/', [\Modules\Order\Http\Controllers\Admin\ShippingMethodController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [\Modules\Order\Http\Controllers\Admin\ShippingMethodController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\Modules\Order\Http\Controllers\Admin\ShippingMethodController::class, 'update'])->name('update');
    Route::post('/{id}/toggle', [\Modules\Order\Http\Controllers\Admin\ShippingMethodController::class, 'toggle'])->name('toggle');
    Route::delete('/{id}', [\Modules\Order\Http\Controllers\Admin\ShippingMethodController::class, 'destroy'])->name('destroy');
});

// Sneat Admin Shipments & Fulfillment Tracking Routes
Route::prefix('admin/shipments')->name('admin.shipments.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [\Modules\Order\Http\Controllers\Admin\ShipmentController::class, 'index'])->name('index');
    Route::get('/{id}', [\Modules\Order\Http\Controllers\Admin\ShipmentController::class, 'show'])->name('show');
    Route::post('/{id}/dispatch', [\Modules\Order\Http\Controllers\Admin\ShipmentController::class, 'dispatchShipment'])->name('dispatch');
    Route::post('/{id}/status', [\Modules\Order\Http\Controllers\Admin\ShipmentController::class, 'updateStatus'])->name('status');
    Route::post('/order/{orderId}', [\Modules\Order\Http\Controllers\Admin\ShipmentController::class, 'createForOrder'])->name('create_for_order');
});

// Sneat Admin Affiliate & Referral Program Routes
Route::prefix('admin/affiliates')->name('admin.affiliates.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [\Modules\Order\Http\Controllers\Admin\AffiliateAdminController::class, 'index'])->name('index');
    Route::post('/{referralId}/paid', [\Modules\Order\Http\Controllers\Admin\AffiliateAdminController::class, 'markPaid'])->name('mark_paid');
    Route::post('/{id}/rate', [\Modules\Order\Http\Controllers\Admin\AffiliateAdminController::class, 'updateRate'])->name('update_rate');
    Route::post('/{id}/toggle-status', [\Modules\Order\Http\Controllers\Admin\AffiliateAdminController::class, 'toggleStatus'])->name('toggle_status');
});

// Storefront /ref/{code} vanity shortcut
Route::middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/ref/{code}', function ($code) {
        $affiliate = app(\Modules\Order\Services\AffiliateService::class)->getAffiliateByCode($code);
        $redirect = redirect('/');
        return $affiliate ? $redirect->withCookie(cookie()->make('sneat_referral_code', $affiliate->affiliate_code, 43200)) : $redirect;
    })->name('affiliate.vanity');
});


