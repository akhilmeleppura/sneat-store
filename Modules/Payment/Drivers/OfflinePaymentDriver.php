<?php

namespace Modules\Payment\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Contracts\PaymentResponse;
use Modules\Payment\Models\PaymentTransaction;

class OfflinePaymentDriver implements PaymentGatewayInterface
{
    public function charge(Order $order, array $options = []): PaymentResponse
    {
        $method = $options['method'] ?? $order->payment_method ?? 'cod';
        $prefix = $method === 'bank_transfer' ? 'wire_' : 'cod_';
        $reference = $prefix . Str::random(12);

        $transaction = PaymentTransaction::create([
            'tenant_id'              => $order->tenant_id,
            'order_id'               => $order->id,
            'transaction_reference'  => $reference,
            'gateway'                => $method,
            'amount'                 => $order->grand_total,
            'currency'               => $order->currency ?? 'USD',
            'status'                 => 'pending',
            'payment_method_details' => [
                'type'        => $method,
                'name'        => $method === 'bank_transfer' ? 'Direct Bank Wire' : 'Cash on Delivery',
                'description' => 'Awaiting manual collection / confirmation',
            ],
            'payload'                => [
                'created_at' => now()->toISOString(),
                'method'     => $method,
            ],
        ]);

        return PaymentResponse::success($reference, $transaction->payload, 'Offline payment recorded; awaiting settlement');
    }

    public function handleWebhook(Request $request): PaymentResponse
    {
        return PaymentResponse::failed('Webhooks are not supported for offline payment methods');
    }

    public function refund(PaymentTransaction $transaction, float $amount, string $reason = ''): PaymentResponse
    {
        $transaction->update([
            'status'  => 'refunded',
            'payload' => array_merge($transaction->payload ?? [], [
                'refund_amount' => $amount,
                'refund_reason' => $reason,
                'refunded_at'   => now()->toISOString(),
            ]),
        ]);

        return PaymentResponse::success('offline_refund_' . Str::random(8), [], 'Offline refund recorded');
    }
}
