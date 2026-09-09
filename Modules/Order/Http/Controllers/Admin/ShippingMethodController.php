<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Context\Services\ContextService;
use Modules\Order\Models\ShippingMethod;

class ShippingMethodController extends Controller
{
    /**
     * Display a listing of shipping carriers and methods.
     */
    public function index(Request $request)
    {
        $query = ShippingMethod::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('carrier', 'like', "%{$search}%");
            });
        }

        if ($request->filled('carrier')) {
            $query->where('carrier', $request->carrier);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('rate_type')) {
            $query->where('rate_type', $request->rate_type);
        }

        $methods = $query->orderBy('sort_order', 'asc')->paginate(15)->withQueryString();

        // KPIs
        $totalMethods = ShippingMethod::count();
        $activeMethods = ShippingMethod::where('is_active', true)->count();
        $uniqueCarriers = ShippingMethod::distinct('carrier')->count('carrier');
        $carriersList = ShippingMethod::distinct()->pluck('carrier')->filter();

        return view('order::admin.shipping-methods.index', compact(
            'methods',
            'totalMethods',
            'activeMethods',
            'uniqueCarriers',
            'carriersList'
        ));
    }

    /**
     * Show the form for creating a new shipping method.
     */
    public function create()
    {
        return view('order::admin.shipping-methods.create');
    }

    /**
     * Store a newly created shipping method.
     */
    public function store(Request $request)
    {
        $tenantId = app(ContextService::class)->currentTenant()?->id ?? \Illuminate\Support\Facades\Auth::user()?->tenant_id ?? 1;

        $validated = $request->validate([
            'name'                    => 'required|string|max:150',
            'code'                    => 'required|string|max:50',
            'carrier'                 => 'required|string|max:100',
            'rate_type'               => 'required|in:flat,tiered_weight,tiered_total,free',
            'base_rate'               => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'min_days'                => 'required|integer|min:0',
            'max_days'                => 'required|integer|min:0|gte:min_days',
            'description'             => 'nullable|string|max:1000',
            'sort_order'              => 'nullable|integer',
            'is_active'               => 'nullable|boolean',
            'weight_tiers'            => 'nullable|array',
            'total_tiers'             => 'nullable|array',
        ]);

        $code = strtoupper(trim($validated['code']));

        // Tenant unique check
        $exists = ShippingMethod::where('code', $code)->where('tenant_id', $tenantId)->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => "Shipping method code '{$code}' already exists for this store."]);
        }

        $settings = [];
        if ($validated['rate_type'] === 'tiered_weight' && ! empty($request->input('weight_tiers'))) {
            $tiers = [];
            foreach ($request->input('weight_tiers') as $tier) {
                if (isset($tier['max_weight']) && isset($tier['rate']) && is_numeric($tier['max_weight'])) {
                    $tiers[] = [
                        'max_weight' => (float) $tier['max_weight'],
                        'rate'       => (float) $tier['rate'],
                    ];
                }
            }
            $settings['weight_tiers'] = $tiers;
        } elseif ($validated['rate_type'] === 'tiered_total' && ! empty($request->input('total_tiers'))) {
            $tiers = [];
            foreach ($request->input('total_tiers') as $tier) {
                if (isset($tier['min_total']) && isset($tier['rate']) && is_numeric($tier['min_total'])) {
                    $tiers[] = [
                        'min_total' => (float) $tier['min_total'],
                        'rate'      => (float) $tier['rate'],
                    ];
                }
            }
            $settings['total_tiers'] = $tiers;
        }

        ShippingMethod::create([
            'tenant_id'               => $tenantId,
            'name'                    => $validated['name'],
            'code'                    => $code,
            'carrier'                 => $validated['carrier'],
            'rate_type'               => $validated['rate_type'],
            'base_rate'               => $validated['base_rate'],
            'free_shipping_threshold' => $validated['free_shipping_threshold'] ?? null,
            'min_days'                => $validated['min_days'],
            'max_days'                => $validated['max_days'],
            'description'             => $validated['description'] ?? null,
            'sort_order'              => $validated['sort_order'] ?? 0,
            'is_active'               => $request->boolean('is_active', true),
            'settings'                => ! empty($settings) ? $settings : null,
        ]);

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', "Shipping method '{$validated['name']}' created successfully.");
    }

    /**
     * Show the form for editing the specified shipping method.
     */
    public function edit(int $id)
    {
        $method = ShippingMethod::findOrFail($id);

        return view('order::admin.shipping-methods.edit', compact('method'));
    }

    /**
     * Update the specified shipping method.
     */
    public function update(Request $request, int $id)
    {
        $method = ShippingMethod::findOrFail($id);
        $tenantId = app(ContextService::class)->currentTenant()?->id ?? \Illuminate\Support\Facades\Auth::user()?->tenant_id ?? 1;

        $validated = $request->validate([
            'name'                    => 'required|string|max:150',
            'code'                    => 'required|string|max:50',
            'carrier'                 => 'required|string|max:100',
            'rate_type'               => 'required|in:flat,tiered_weight,tiered_total,free',
            'base_rate'               => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'min_days'                => 'required|integer|min:0',
            'max_days'                => 'required|integer|min:0|gte:min_days',
            'description'             => 'nullable|string|max:1000',
            'sort_order'              => 'nullable|integer',
            'is_active'               => 'nullable|boolean',
            'weight_tiers'            => 'nullable|array',
            'total_tiers'             => 'nullable|array',
        ]);

        $code = strtoupper(trim($validated['code']));

        // Check unique code excluding self
        $exists = ShippingMethod::where('code', $code)
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => "Shipping method code '{$code}' already exists for another method."]);
        }

        $settings = $method->settings ?? [];
        if ($validated['rate_type'] === 'tiered_weight') {
            $tiers = [];
            if ($request->filled('weight_tiers')) {
                foreach ($request->input('weight_tiers') as $tier) {
                    if (isset($tier['max_weight']) && isset($tier['rate']) && is_numeric($tier['max_weight'])) {
                        $tiers[] = [
                            'max_weight' => (float) $tier['max_weight'],
                            'rate'       => (float) $tier['rate'],
                        ];
                    }
                }
            }
            $settings['weight_tiers'] = $tiers;
        } elseif ($validated['rate_type'] === 'tiered_total') {
            $tiers = [];
            if ($request->filled('total_tiers')) {
                foreach ($request->input('total_tiers') as $tier) {
                    if (isset($tier['min_total']) && isset($tier['rate']) && is_numeric($tier['min_total'])) {
                        $tiers[] = [
                            'min_total' => (float) $tier['min_total'],
                            'rate'      => (float) $tier['rate'],
                        ];
                    }
                }
            }
            $settings['total_tiers'] = $tiers;
        }

        $method->update([
            'name'                    => $validated['name'],
            'code'                    => $code,
            'carrier'                 => $validated['carrier'],
            'rate_type'               => $validated['rate_type'],
            'base_rate'               => $validated['base_rate'],
            'free_shipping_threshold' => $validated['free_shipping_threshold'] ?? null,
            'min_days'                => $validated['min_days'],
            'max_days'                => $validated['max_days'],
            'description'             => $validated['description'] ?? null,
            'sort_order'              => $validated['sort_order'] ?? 0,
            'is_active'               => $request->boolean('is_active', true),
            'settings'                => ! empty($settings) ? $settings : null,
        ]);

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', "Shipping method '{$method->name}' updated successfully.");
    }

    /**
     * Remove the specified shipping method.
     */
    public function destroy(int $id)
    {
        $method = ShippingMethod::findOrFail($id);
        $methodName = $method->name;
        $method->delete();

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', "Shipping method '{$methodName}' deleted successfully.");
    }

    /**
     * Toggle status active/inactive.
     */
    public function toggle(int $id)
    {
        $method = ShippingMethod::findOrFail($id);
        $method->is_active = ! $method->is_active;
        $method->save();

        $statusText = $method->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "Shipping method '{$method->name}' {$statusText}.");
    }
}
