<?php

namespace Modules\Payment\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Contracts\PaymentResponse;
use Modules\Payment\Models\PaymentTransaction;

class MockPaymentDriver implements PaymentGatewayInterface
{
    public function charge(Order $order, array $options = []): PaymentResponse
    {
        $reference = 'mock_txn_' . Str::random(16);

        // Record transaction
        $transaction = PaymentTransaction::create([
            'tenant_id'              => $order->tenant_id,
            'order_id'               => $order->id,
            'transaction_reference'  => $reference,
            'gateway'                => 'mock',
            'amount'                 => $order->grand_total,
            'currency'               => $order->currency ?? 'USD',
            'status'                 => 'successful',
            'payment_method_details' => [
                'type'        => 'mock_card',
                'brand'       => 'Visa',
                'last4'       => '4242',
                'description' => 'Mock Sandbox Transaction',
            ],
            'payload'                => [
                'simulated_at'   => now()->toISOString(),
                'status'         => 'authorized_and_captured',
                'options_passed' => $options,
            ],
        ]);

        return PaymentResponse::success($reference, $transaction->payload, 'Simulated payment succeeded');
    }

    public function handleWebhook(Request $request): PaymentResponse
    {
        $reference = $request->input('transaction_reference') ?? ('mock_hook_' . Str::random(12));
        $status    = $request->input('status', 'successful');

        return PaymentResponse::success($reference, $request->all(), "Mock webhook processed with status {$status}");
    }

    public function refund(PaymentTransaction $transaction, float $amount, string $reason = ''): PaymentResponse
    {
        $refundRef = 'mock_ref_' . Str::random(16);

        $transaction->update([
            'status'  => 'refunded',
            'payload' => array_merge($transaction->payload ?? [], [
                'refund_reference' => $refundRef,
                'refund_amount'    => $amount,
                'refund_reason'    => $reason,
                'refunded_at'      => now()->toISOString(),
            ]),
        ]);

        return PaymentResponse::success($refundRef, [], "Mock refund of \${$amount} successful");
    }
}
