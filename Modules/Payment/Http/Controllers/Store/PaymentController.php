<?php

namespace Modules\Payment\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Modules\Payment\Services\FinancialSettlementService;
use Modules\Payment\Services\PaymentManager;

class PaymentController extends Controller
{
    protected PaymentManager $paymentManager;
    protected FinancialSettlementService $settlementService;

    public function __construct(
        PaymentManager $paymentManager,
        FinancialSettlementService $settlementService
    ) {
        $this->paymentManager = $paymentManager;
        $this->settlementService = $settlementService;
    }

    /**
     * Process payment for an existing order.
     */
    public function process(string $orderNumber)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        // If order already paid, send directly to confirmation
        if ($order->payment_status === 'paid') {
            return redirect()->route('store.order.confirmation', $order->order_number);
        }

        $method = $order->payment_method;
        $driverName = match ($method) {
            'stripe'        => 'stripe',
            'paypal'        => 'paypal',
            'cod'           => 'offline',
            'bank_transfer' => 'offline',
            default         => 'mock',
        };

        $driver = $this->paymentManager->driver($driverName);
        $response = $driver->charge($order, ['method' => $method]);

        if ($response->redirectUrl) {
            return redirect()->away($response->redirectUrl);
        }

        if ($response->successful) {
            if ($method === 'mock') {
                $this->settlementService->settleOrder($order, $response->transactionReference);
            }
            return redirect()->route('store.order.confirmation', $order->order_number)
                ->with('success', $response->message ?? 'Payment recorded successfully.');
        }

        return redirect()->route('store.checkout')
            ->with('error', $response->message ?? 'Payment initiation failed. Please try another method.');
    }

    /**
     * Return callback from gateway.
     */
    public function callback(Request $request, string $gateway, string $orderNumber)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        $reference = $request->input('session_id') ?? $request->input('token');

        // Execute financial settlement
        $this->settlementService->settleOrder($order, $reference);

        return redirect()->route('store.order.confirmation', $order->order_number)
            ->with('success', 'Payment verified and order successfully confirmed!');
    }
}
