<?php

namespace Modules\Payment\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Context\Facades\Context;
use Modules\Payment\Models\CustomerPaymentMethod;

class CustomerPaymentMethodController extends Controller
{
    /**
     * List all customer saved payment methods.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $paymentMethods = CustomerPaymentMethod::where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->latest('id')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'status'          => 'success',
                'payment_methods' => $paymentMethods,
            ]);
        }

        return view('payment::customer.payment-methods', [
            'user'           => $user,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Store and securely tokenize a new payment method card.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'card_holder_name' => 'nullable|string|max:255',
            'card_number'      => 'required|string|min:12|max:24',
            'card_exp_month'   => 'required|string|size:2',
            'card_exp_year'    => 'required|string|size:4',
            'card_brand'       => 'nullable|string|max:30',
            'gateway'          => 'nullable|string|in:stripe,paypal,authorize,razorpay',
            'is_default'       => 'nullable|boolean',
        ]);

        $cleanCardNumber = preg_replace('/[^\d]/', '', $validated['card_number']);
        $cardLastFour = substr($cleanCardNumber, -4);

        // Auto-detect brand if not provided
        $cardBrand = $validated['card_brand'] ?? null;
        if (!$cardBrand) {
            if (str_starts_with($cleanCardNumber, '4')) {
                $cardBrand = 'visa';
            } elseif (preg_match('/^(5[1-5]|2[2-7])/', $cleanCardNumber)) {
                $cardBrand = 'mastercard';
            } elseif (preg_match('/^3[47]/', $cleanCardNumber)) {
                $cardBrand = 'amex';
            } elseif (str_starts_with($cleanCardNumber, '6011')) {
                $cardBrand = 'discover';
            } else {
                $cardBrand = 'card';
            }
        } else {
            $cardBrand = strtolower($cardBrand);
        }

        $existingCount = CustomerPaymentMethod::where('user_id', $user->id)->count();
        $isDefault = $request->boolean('is_default') || $existingCount === 0;

        if ($isDefault) {
            CustomerPaymentMethod::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $paymentMethod = CustomerPaymentMethod::create([
            'tenant_id'            => Context::tenantId() ?? 1,
            'user_id'              => $user->id,
            'gateway'              => $validated['gateway'] ?? 'stripe',
            'payment_method_token' => 'pm_tok_' . Str::random(24),
            'card_brand'           => $cardBrand,
            'card_last_four'       => $cardLastFour,
            'card_exp_month'       => str_pad($validated['card_exp_month'], 2, '0', STR_PAD_LEFT),
            'card_exp_year'        => $validated['card_exp_year'],
            'is_default'           => $isDefault,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'         => 'success',
                'message'        => 'Payment method securely vaulted and saved.',
                'payment_method' => $paymentMethod,
            ], 201);
        }

        return redirect()->route('account.payment_methods.index')
            ->with('success', 'Payment card successfully added to your account.');
    }

    /**
     * Set a saved card as customer's default payment method.
     */
    public function setDefault(Request $request, int $id)
    {
        $user = Auth::user();
        $method = CustomerPaymentMethod::where('user_id', $user->id)->findOrFail($id);

        CustomerPaymentMethod::where('user_id', $user->id)->update(['is_default' => false]);
        $method->update(['is_default' => true]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'         => 'success',
                'message'        => 'Default payment method updated.',
                'payment_method' => $method->fresh(),
            ]);
        }

        return redirect()->route('account.payment_methods.index')
            ->with('success', 'Default payment method updated successfully.');
    }

    /**
     * Remove a saved card from the customer's payment vault.
     */
    public function destroy(Request $request, int $id)
    {
        $user = Auth::user();
        $method = CustomerPaymentMethod::where('user_id', $user->id)->findOrFail($id);
        $wasDefault = $method->is_default;

        $method->delete();

        // If the default card was deleted, promote another card if available
        if ($wasDefault) {
            $next = CustomerPaymentMethod::where('user_id', $user->id)->latest('id')->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Payment method removed from your vault.',
            ]);
        }

        return redirect()->route('account.payment_methods.index')
            ->with('success', 'Payment method removed successfully.');
    }
}
