<?php

namespace Modules\Payment\Services;

use Illuminate\Support\Manager;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Drivers\MockPaymentDriver;
use Modules\Payment\Drivers\OfflinePaymentDriver;
use Modules\Payment\Drivers\PayPalPaymentDriver;
use Modules\Payment\Drivers\StripePaymentDriver;

class PaymentManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return config('payment.default', 'mock');
    }

    /**
     * Create the Stripe driver.
     */
    protected function createStripeDriver(): PaymentGatewayInterface
    {
        return new StripePaymentDriver(config('payment.gateways.stripe', []));
    }

    /**
     * Create the PayPal driver.
     */
    protected function createPaypalDriver(): PaymentGatewayInterface
    {
        return new PayPalPaymentDriver(config('payment.gateways.paypal', []));
    }

    /**
     * Create the Offline driver (COD / Bank Wire).
     */
    protected function createOfflineDriver(): PaymentGatewayInterface
    {
        return new OfflinePaymentDriver();
    }

    /**
     * Create the Mock / Sandbox driver.
     */
    protected function createMockDriver(): PaymentGatewayInterface
    {
        return new MockPaymentDriver();
    }
}
