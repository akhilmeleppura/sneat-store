<?php

namespace Modules\Marketplace\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Models\VendorPayout;

class VendorPayoutController extends Controller
{
    protected function getVendor(Request $request): Vendor
    {
        return $request->get('current_vendor')
            ?? Vendor::where('user_id', Auth::id())->firstOrFail();
    }

    /**
     * Display payout ledger and withdrawal form.
     */
    public function index(Request $request)
    {
        $vendor = $this->getVendor($request);

        $payouts = VendorPayout::where('vendor_id', $vendor->id)
            ->latest()
            ->paginate(15);

        $minPayout = (float) config('marketplace.min_payout_amount', 50.00);

        return view('marketplace::vendor.payouts.index', compact('vendor', 'payouts', 'minPayout'));
    }

    /**
     * Submit a payout withdrawal request.
     */
    public function store(Request $request)
    {
        $vendor = $this->getVendor($request);
        $minPayout = (float) config('marketplace.min_payout_amount', 50.00);

        $validated = $request->validate([
            'amount'        => "required|numeric|min:{$minPayout}|max:{$vendor->balance}",
            'payout_method' => 'required|string|in:bank_transfer,paypal,stripe',
            'notes'         => 'nullable|string|max:500',
        ]);

        $amount = (float) $validated['amount'];

        $payout = VendorPayout::create([
            'tenant_id'     => $vendor->tenant_id,
            'vendor_id'     => $vendor->id,
            'amount'        => $amount,
            'currency'      => 'USD',
            'status'        => 'requested',
            'payout_method' => $validated['payout_method'],
            'notes'         => $validated['notes'] ?? null,
        ]);

        event(new \Modules\Marketplace\Events\VendorPayoutRequestedEvent($payout));

        return redirect()->route('vendor.payouts.index')
            ->with('success', "Payout request of \${$amount} submitted successfully and is awaiting review.");
    }
}
