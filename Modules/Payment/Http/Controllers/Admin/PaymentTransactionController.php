<?php

namespace Modules\Payment\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Payment\Services\PaymentManager;

class PaymentTransactionController extends Controller
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Display listing of payment transactions in Sneat Admin.
     */
    public function index(Request $request)
    {
        $query = PaymentTransaction::with(['order.store'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('transaction_reference', 'like', "%{$term}%")
                  ->orWhereHas('order', function ($oq) use ($term) {
                      $oq->where('order_number', 'like', "%{$term}%")
                         ->orWhere('customer_name', 'like', "%{$term}%");
                  });
            });
        }

        $transactions = $query->paginate(15)->withQueryString();

        return view('payment::admin.index', compact('transactions'));
    }

    /**
     * Issue refund on a payment transaction.
     */
    public function refund(Request $request, int $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $transaction = PaymentTransaction::findOrFail($id);

        try {
            $driver = $this->paymentManager->driver($transaction->gateway);
            $response = $driver->refund($transaction, (float) $request->amount, $request->reason ?? 'Customer refund requested');

            if ($response->successful) {
                return redirect()->back()->with('success', 'Refund successfully processed: ' . $response->message);
            }

            return redirect()->back()->with('error', 'Refund failed: ' . $response->message);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Refund failed: ' . $e->getMessage());
        }
    }
}
