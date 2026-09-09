<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\TaxRate;

class AdminTaxRateController extends Controller
{
    /**
     * Display all tax jurisdictions and rates.
     */
    public function index(Request $request)
    {
        $query = TaxRate::withoutTenancy()->latest('id');

        if ($request->filled('country')) {
            $query->where('country_code', strtoupper($request->query('country')));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('tax_name', 'like', "%{$search}%")
                    ->orWhere('country_code', 'like', "%{$search}%")
                    ->orWhere('state_code', 'like', "%{$search}%");
            });
        }

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'data'   => $query->paginate(20),
            ]);
        }

        $taxRates = $query->paginate(20)->withQueryString();

        $stats = [
            'total'          => TaxRate::withoutTenancy()->count(),
            'active'         => TaxRate::withoutTenancy()->where('is_active', true)->count(),
            'b2b_exempt'     => TaxRate::withoutTenancy()->where('is_b2b_exempt', true)->count(),
            'compound_rates' => TaxRate::withoutTenancy()->where('is_compound', true)->count(),
        ];

        return view('order::admin.tax_rates.index', compact('taxRates', 'stats'));
    }

    /**
     * Store new regional tax rate.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_code'    => 'required|string|max:2',
            'state_code'      => 'nullable|string|max:10',
            'tax_name'        => 'required|string|max:100',
            'rate_percentage' => 'required|numeric|min:0|max:100',
            'is_compound'     => 'nullable|boolean',
            'is_b2b_exempt'   => 'nullable|boolean',
            'is_active'       => 'nullable|boolean',
        ]);

        $taxRate = TaxRate::create([
            'tenant_id'       => \Modules\Context\Facades\Context::tenantId() ?? 1,
            'country_code'    => strtoupper($validated['country_code']),
            'state_code'      => !empty($validated['state_code']) ? strtoupper($validated['state_code']) : null,
            'tax_name'        => $validated['tax_name'],
            'rate_percentage' => $validated['rate_percentage'],
            'is_compound'     => $request->boolean('is_compound'),
            'is_b2b_exempt'   => $request->boolean('is_b2b_exempt'),
            'is_active'       => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status'   => 'success',
                'message'  => "Tax rate {$taxRate->tax_name} created successfully.",
                'tax_rate' => $taxRate,
            ]);
        }

        return redirect()->back()
            ->with('success', "Tax rule {$taxRate->tax_name} ({$taxRate->rate_percentage}%) added successfully.");
    }

    /**
     * Update tax rate.
     */
    public function update(Request $request, int $id)
    {
        $taxRate = TaxRate::withoutTenancy()->findOrFail($id);

        $validated = $request->validate([
            'country_code'    => 'required|string|max:2',
            'state_code'      => 'nullable|string|max:10',
            'tax_name'        => 'required|string|max:100',
            'rate_percentage' => 'required|numeric|min:0|max:100',
            'is_compound'     => 'nullable|boolean',
            'is_b2b_exempt'   => 'nullable|boolean',
            'is_active'       => 'nullable|boolean',
        ]);

        $taxRate->update([
            'country_code'    => strtoupper($validated['country_code']),
            'state_code'      => !empty($validated['state_code']) ? strtoupper($validated['state_code']) : null,
            'tax_name'        => $validated['tax_name'],
            'rate_percentage' => $validated['rate_percentage'],
            'is_compound'     => $request->boolean('is_compound'),
            'is_b2b_exempt'   => $request->boolean('is_b2b_exempt'),
            'is_active'       => $request->boolean('is_active'),
        ]);

        return redirect()->back()
            ->with('success', "Tax rate {$taxRate->tax_name} updated successfully.");
    }

    /**
     * Toggle active state.
     */
    public function toggle(int $id)
    {
        $taxRate = TaxRate::withoutTenancy()->findOrFail($id);
        $taxRate->is_active = !$taxRate->is_active;
        $taxRate->save();

        return redirect()->back()
            ->with('success', "Tax rate {$taxRate->tax_name} status toggled.");
    }

    /**
     * Delete tax rate.
     */
    public function destroy(int $id)
    {
        $taxRate = TaxRate::withoutTenancy()->findOrFail($id);
        $name = $taxRate->tax_name;
        $taxRate->delete();

        return redirect()->back()
            ->with('success', "Tax rule {$name} removed.");
    }
}
