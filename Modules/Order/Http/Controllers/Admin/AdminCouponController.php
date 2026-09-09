<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Context\Services\ContextService;
use Modules\Order\Models\Coupon;
use Modules\Order\Models\CouponUsage;

class AdminCouponController extends Controller
{
    /**
     * Display a listing of coupons with KPIs and search filters.
     */
    public function index(Request $request)
    {
        $query = Coupon::with(['usages'])->latest();

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'expired') {
                $query->where('expires_at', '<', now());
            } elseif ($status === 'disabled') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $coupons = $query->paginate(15)->withQueryString();

        // Calculate Overview KPIs
        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::active()->count();
        $totalRedemptions = CouponUsage::count();
        $totalSavings = (float) CouponUsage::sum('discount_amount');

        return view('order::admin.coupons.index', compact(
            'coupons',
            'totalCoupons',
            'activeCoupons',
            'totalRedemptions',
            'totalSavings'
        ));
    }

    /**
     * Show the form for creating a new coupon.
     */
    public function create()
    {
        $vendors = [];
        if (class_exists(\Modules\Marketplace\Models\MarketplaceVendor::class)) {
            $vendors = \Modules\Marketplace\Models\MarketplaceVendor::orderBy('name')->get();
        }

        return view('order::admin.coupons.create', compact('vendors'));
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function store(Request $request)
    {
        $tenantId = app(ContextService::class)->currentTenant()?->id ?? \Illuminate\Support\Facades\Auth::user()?->tenant_id ?? 1;

        $validated = $request->validate([
            'code'                => 'required|string|max:50',
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string|max:1000',
            'type'                => 'required|in:percentage,fixed',
            'value'               => 'required|numeric|min:0.01' . ($request->type === 'percentage' ? '|max:100' : ''),
            'min_order_amount'    => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'         => 'nullable|integer|min:1',
            'usage_limit_per_user'=> 'nullable|integer|min:1',
            'starts_at'           => 'nullable|date',
            'expires_at'          => 'nullable|date|after_or_equal:starts_at',
            'vendor_id'           => 'nullable|integer',
            'is_active'           => 'nullable|boolean',
        ]);

        $code = strtoupper(trim($validated['code']));

        // Tenant unique check
        $exists = Coupon::where('code', $code)->where('tenant_id', $tenantId)->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['code' => "Coupon code '{$code}' already exists for this store."]);
        }

        Coupon::create([
            'tenant_id'            => $tenantId,
            'vendor_id'            => $validated['vendor_id'] ?? null,
            'code'                 => $code,
            'name'                 => $validated['name'],
            'description'          => $validated['description'] ?? null,
            'type'                 => $validated['type'],
            'value'                => $validated['value'],
            'min_order_amount'     => $validated['min_order_amount'] ?? 0.00,
            'max_discount_amount'  => $validated['max_discount_amount'] ?? null,
            'usage_limit'          => $validated['usage_limit'] ?? null,
            'usage_limit_per_user' => $validated['usage_limit_per_user'] ?? 1,
            'starts_at'            => $validated['starts_at'] ?? null,
            'expires_at'           => $validated['expires_at'] ?? null,
            'is_active'            => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon '{$code}' created successfully.");
    }

    /**
     * Show the form for editing the specified coupon.
     */
    public function edit(int $id)
    {
        $coupon = Coupon::findOrFail($id);

        $vendors = [];
        if (class_exists(\Modules\Marketplace\Models\MarketplaceVendor::class)) {
            $vendors = \Modules\Marketplace\Models\MarketplaceVendor::orderBy('name')->get();
        }

        return view('order::admin.coupons.edit', compact('coupon', 'vendors'));
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(Request $request, int $id)
    {
        $coupon = Coupon::findOrFail($id);
        $tenantId = $coupon->tenant_id;

        $validated = $request->validate([
            'code'                => 'required|string|max:50',
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string|max:1000',
            'type'                => 'required|in:percentage,fixed',
            'value'               => 'required|numeric|min:0.01' . ($request->type === 'percentage' ? '|max:100' : ''),
            'min_order_amount'    => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'         => 'nullable|integer|min:1',
            'usage_limit_per_user'=> 'nullable|integer|min:1',
            'starts_at'           => 'nullable|date',
            'expires_at'          => 'nullable|date|after_or_equal:starts_at',
            'vendor_id'           => 'nullable|integer',
            'is_active'           => 'nullable|boolean',
        ]);

        $code = strtoupper(trim($validated['code']));

        // Tenant unique check excluding current
        $exists = Coupon::where('code', $code)
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['code' => "Coupon code '{$code}' already exists for another coupon."]);
        }

        $coupon->update([
            'vendor_id'            => $validated['vendor_id'] ?? null,
            'code'                 => $code,
            'name'                 => $validated['name'],
            'description'          => $validated['description'] ?? null,
            'type'                 => $validated['type'],
            'value'                => $validated['value'],
            'min_order_amount'     => $validated['min_order_amount'] ?? 0.00,
            'max_discount_amount'  => $validated['max_discount_amount'] ?? null,
            'usage_limit'          => $validated['usage_limit'] ?? null,
            'usage_limit_per_user' => $validated['usage_limit_per_user'] ?? 1,
            'starts_at'            => $validated['starts_at'] ?? null,
            'expires_at'           => $validated['expires_at'] ?? null,
            'is_active'            => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon '{$code}' updated successfully.");
    }

    /**
     * Fast toggle active / disabled status.
     */
    public function toggleStatus(int $id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->is_active = ! $coupon->is_active;
        $coupon->save();

        $status = $coupon->is_active ? 'activated' : 'deactivated';
        return redirect()->back()
            ->with('success', "Coupon '{$coupon->code}' has been {$status}.");
    }

    /**
     * Remove the specified coupon from storage.
     */
    public function destroy(int $id)
    {
        $coupon = Coupon::findOrFail($id);
        $code = $coupon->code;
        $coupon->delete();

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon '{$code}' deleted successfully.");
    }
}
