<?php

namespace Modules\Payment\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Order\Models\Order;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Payment\Services\FinancialSettlementService;
use Modules\Payment\Services\PaymentManager;

class PaymentWebhookController extends Controller
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
     * Handle incoming gateway webhook.
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        Log::info("Incoming webhook for gateway [{$gateway}]", $request->all());

        try {
            $driver = $this->paymentManager->driver($gateway);
            $response = $driver->handleWebhook($request);

            if (!$response->successful) {
                return response()->json(['error' => $response->message], 400);
            }

            // Find transaction or order
            $reference = $response->transactionReference;
            $transaction = PaymentTransaction::where('transaction_reference', $reference)->first();

            $order = null;
            if ($transaction) {
                $order = $transaction->order;
            } else {
                // Check metadata order_number
                $orderNumber = $request->input('data.object.metadata.order_number')
                    ?? $request->input('order_number');
                if ($orderNumber) {
                    $order = Order::where('order_number', $orderNumber)->first();
                }
            }

            if ($order) {
                $settlementResult = $this->settlementService->settleOrder($order, $reference);
                return response()->json([
                    'received'   => true,
                    'order'      => $order->order_number,
                    'settlement' => $settlementResult,
                ]);
            }

            return response()->json([
                'received' => true,
                'message'  => 'Webhook accepted, order not linked or awaiting match',
            ]);
        } catch (\Throwable $e) {
            Log::error("Webhook error for [{$gateway}]: " . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed: ' . $e->getMessage()], 500);
        }
    }
}
