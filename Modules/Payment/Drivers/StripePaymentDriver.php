<?php

namespace Modules\Payment\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Contracts\PaymentResponse;
use Modules\Payment\Models\PaymentTransaction;

class StripePaymentDriver implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function charge(Order $order, array $options = []): PaymentResponse
    {
        $secretKey = $this->config['secret_key'] ?? config('payment.gateways.stripe.secret_key');
        $reference = 'cs_test_' . Str::random(24);

        if (!empty($secretKey) && class_exists(\Stripe\Stripe::class)) {
            try {
                \Stripe\Stripe::setApiKey($secretKey);

                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items'           => [
                        [
                            'price_data' => [
                                'currency'     => strtolower($order->currency ?? 'usd'),
                                'product_data' => [
                                    'name' => "Order #{$order->order_number}",
                                ],
                                'unit_amount'  => (int) round($order->grand_total * 100),
                            ],
                            'quantity'   => 1,
                        ],
                    ],
                    'mode'                 => 'payment',
                    'success_url'          => url("/store/payment/callback/stripe/{$order->order_number}?session_id={CHECKOUT_SESSION_ID}"),
                    'cancel_url'           => url("/store/checkout"),
                    'client_reference_id'  => (string) $order->id,
                    'customer_email'       => $order->customer_email,
                    'metadata'             => [
                        'order_number' => $order->order_number,
                        'tenant_id'    => $order->tenant_id,
                    ],
                ]);

                $reference = $session->id;
                $redirectUrl = $session->url;
            } catch (\Throwable $e) {
                Log::error('Stripe Checkout creation failed: ' . $e->getMessage());
                return PaymentResponse::failed('Stripe service error: ' . $e->getMessage());
            }
        } else {
            // Simulated Stripe checkout redirect for development/demo
            $redirectUrl = url("/store/payment/callback/stripe/{$order->order_number}?simulated=1&session_id={$reference}");
        }

        PaymentTransaction::create([
            'tenant_id'              => $order->tenant_id,
            'order_id'               => $order->id,
            'transaction_reference'  => $reference,
            'gateway'                => 'stripe',
            'amount'                 => $order->grand_total,
            'currency'               => $order->currency ?? 'USD',
            'status'                 => 'pending',
            'payment_method_details' => [
                'provider' => 'Stripe Checkout',
            ],
            'payload'                => [
                'session_id' => $reference,
                'mode'       => 'checkout',
            ],
        ]);

        return PaymentResponse::redirect($redirectUrl, $reference);
    }

    public function handleWebhook(Request $request): PaymentResponse
    {
        $payload = $request->all();
        $event = $payload['type'] ?? '';

        Log::info('Stripe Webhook Received: ' . $event);

        if ($event === 'checkout.session.completed' || $event === 'payment_intent.succeeded') {
            $session = $payload['data']['object'] ?? [];
            $reference = $session['id'] ?? null;

            return PaymentResponse::success($reference ?? 'stripe_event_' . Str::random(8), $payload);
        }

        return PaymentResponse::failed("Unhandled Stripe event type: {$event}");
    }

    public function refund(PaymentTransaction $transaction, float $amount, string $reason = ''): PaymentResponse
    {
        $secretKey = $this->config['secret_key'] ?? config('payment.gateways.stripe.secret_key');
        $refundRef = 're_test_' . Str::random(24);

        if (!empty($secretKey) && class_exists(\Stripe\Refund::class)) {
            try {
                \Stripe\Stripe::setApiKey($secretKey);
                $refund = \Stripe\Refund::create([
                    'payment_intent' => $transaction->transaction_reference,
                    'amount'         => (int) round($amount * 100),
                    'reason'         => 'requested_by_customer',
                ]);
                $refundRef = $refund->id;
            } catch (\Throwable $e) {
                Log::error('Stripe Refund failed: ' . $e->getMessage());
                return PaymentResponse::failed('Stripe refund error: ' . $e->getMessage());
            }
        }

        $transaction->update([
            'status'  => 'refunded',
            'payload' => array_merge($transaction->payload ?? [], [
                'refund_id'     => $refundRef,
                'refund_amount' => $amount,
                'refund_reason' => $reason,
                'refunded_at'   => now()->toISOString(),
            ]),
        ]);

        return PaymentResponse::success($refundRef, [], "Refund of \${$amount} issued via Stripe");
    }
}
