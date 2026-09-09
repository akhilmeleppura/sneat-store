<?php

namespace Modules\Payment\Contracts;

use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Modules\Payment\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    /**
     * Process or initiate a charge for the given order.
     */
    public function charge(Order $order, array $options = []): PaymentResponse;

    /**
     * Handle incoming asynchronous webhook from the payment provider.
     */
    public function handleWebhook(Request $request): PaymentResponse;

    /**
     * Refund a previous transaction.
     */
    public function refund(PaymentTransaction $transaction, float $amount, string $reason = ''): PaymentResponse;
}
