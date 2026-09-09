<?php

namespace Modules\Payment\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Contracts\PaymentResponse;
use Modules\Payment\Models\PaymentTransaction;

class PayPalPaymentDriver implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function charge(Order $order, array $options = []): PaymentResponse
    {
        $orderId = 'PAYPAL_ORD_' . Str::random(16);
        $redirectUrl = url("/store/payment/callback/paypal/{$order->order_number}?simulated=1&token={$orderId}");

        PaymentTransaction::create([
            'tenant_id'              => $order->tenant_id,
            'order_id'               => $order->id,
            'transaction_reference'  => $orderId,
            'gateway'                => 'paypal',
            'amount'                 => $order->grand_total,
            'currency'               => $order->currency ?? 'USD',
            'status'                 => 'pending',
            'payment_method_details' => [
                'provider' => 'PayPal Checkout',
            ],
            'payload'                => [
                'token' => $orderId,
                'mode'  => $this->config['mode'] ?? 'sandbox',
            ],
        ]);

        return PaymentResponse::redirect($redirectUrl, $orderId);
    }

    public function handleWebhook(Request $request): PaymentResponse
    {
        $payload = $request->all();
        $eventType = $payload['event_type'] ?? '';

        Log::info('PayPal Webhook Received: ' . $eventType);

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED' || $eventType === 'CHECKOUT.ORDER.APPROVED') {
            $resource = $payload['resource'] ?? [];
            $reference = $resource['id'] ?? null;

            return PaymentResponse::success($reference ?? ('paypal_event_' . Str::random(8)), $payload);
        }

        return PaymentResponse::failed("Unhandled PayPal event type: {$eventType}");
    }

    public function refund(PaymentTransaction $transaction, float $amount, string $reason = ''): PaymentResponse
    {
        $refundId = 'paypal_ref_' . Str::random(16);

        $transaction->update([
            'status'  => 'refunded',
            'payload' => array_merge($transaction->payload ?? [], [
                'refund_id'     => $refundId,
                'refund_amount' => $amount,
                'refund_reason' => $reason,
                'refunded_at'   => now()->toISOString(),
            ]),
        ]);

        return PaymentResponse::success($refundId, [], "Refund of \${$amount} issued via PayPal");
    }
}
