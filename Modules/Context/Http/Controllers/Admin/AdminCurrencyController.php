<?php

namespace Modules\Context\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Context\Models\Currency;
use Modules\Context\Services\CurrencyService;

class AdminCurrencyController extends Controller
{
    /**
     * Display currencies and exchange rates management panel.
     */
    public function index(CurrencyService $currencyService)
    {
        // Ensure defaults are seeded
        $currencyService->getActiveCurrencies();

        $currencies = Currency::orderByDesc('is_default')
            ->orderBy('code')
            ->get();

        $defaultCurrency = $currencyService->getDefaultCurrency();
        $totalCurrencies = $currencies->count();
        $activeCount = $currencies->where('is_active', true)->count();

        return view('context::admin.currencies.index', compact(
            'currencies',
            'defaultCurrency',
            'totalCurrencies',
            'activeCount'
        ));
    }

    /**
     * Store a new custom currency.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'            => 'required|string|size:3',
            'name'            => 'required|string|max:50',
            'symbol'          => 'required|string|max:10',
            'exchange_rate'   => 'required|numeric|gt:0',
            'decimal_places'  => 'required|integer|min:0|max:4',
            'symbol_position' => 'required|in:before,after',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['is_default'] = false;
        $validated['is_active'] = true;

        Currency::create($validated);

        return back()->with('success', "Currency {$validated['code']} created successfully.");
    }

    /**
     * Update exchange rate for a currency.
     */
    public function updateRate(Request $request, int $id)
    {
        $request->validate([
            'exchange_rate' => 'required|numeric|gt:0',
        ]);

        $currency = Currency::findOrFail($id);

        if ($currency->is_default) {
            return back()->with('error', 'Base default currency rate must remain 1.000000.');
        }

        $currency->update([
            'exchange_rate' => (float) $request->exchange_rate,
        ]);

        return back()->with('success', "Exchange rate for {$currency->code} updated to {$currency->exchange_rate}.");
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(int $id)
    {
        $currency = Currency::findOrFail($id);

        if ($currency->is_default && $currency->is_active) {
            return back()->with('error', 'Cannot disable the primary default base currency.');
        }

        $currency->update([
            'is_active' => ! $currency->is_active,
        ]);

        $status = $currency->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "Currency {$currency->code} has been {$status}.");
    }

    /**
     * Set a currency as the primary default base currency.
     */
    public function setDefault(int $id, CurrencyService $currencyService)
    {
        $currency = Currency::findOrFail($id);
        $currencyService->setDefaultCurrency($currency->code);

        return back()->with('success', "{$currency->code} ({$currency->name}) is now the primary base currency.");
    }
}
