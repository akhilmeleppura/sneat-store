<?php

namespace Modules\Context\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Context\Services\CurrencyService;

class CurrencySwitcherController extends Controller
{
    /**
     * Switch active storefront / application currency.
     */
    public function switchCurrency(Request $request, CurrencyService $currencyService)
    {
        $code = $request->input('currency') ?? $request->input('code');

        if (! $code) {
            return back()->with('error', 'Please select a valid currency.');
        }

        $code = strtoupper(trim($code));
        $switched = $currencyService->setCurrentCurrency($code);

        if (! $switched) {
            return back()->with('error', "Currency {$code} is currently unavailable.");
        }

        $current = $currencyService->getCurrentCurrency();

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => "Switched currency to {$current->code} ({$current->symbol})",
                'currency' => $current,
            ]);
        }

        return back()->with('success', "Switched currency to {$current->code} ({$current->symbol})");
    }

    /**
     * Switch via GET route: /currency/{code}
     */
    public function switchByGet(string $code, CurrencyService $currencyService)
    {
        $code = strtoupper(trim($code));
        $currencyService->setCurrentCurrency($code);
        $current = $currencyService->getCurrentCurrency();

        return back()->with('success', "Currency switched to {$current->code} ({$current->symbol})");
    }

    /**
     * API: Get list of active currencies.
     */
    public function active(CurrencyService $currencyService)
    {
        return response()->json([
            'current'    => $currencyService->getCurrentCurrency(),
            'currencies' => $currencyService->getActiveCurrencies(),
        ]);
    }
}
