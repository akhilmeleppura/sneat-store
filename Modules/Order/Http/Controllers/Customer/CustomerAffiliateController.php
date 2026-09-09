<?php

namespace Modules\Order\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Services\AffiliateService;

class CustomerAffiliateController extends Controller
{
    public function __construct(protected AffiliateService $affiliateService)
    {
    }

    /**
     * Display customer referral dashboard and link.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $affiliate = $this->affiliateService->getOrCreateAffiliateForUser($user);
        $referrals = $affiliate->referrals()->with('order')->paginate(15);

        return view('order::customer.referrals.index', compact('affiliate', 'referrals'));
    }

    /**
     * Update payout details.
     */
    public function updatePayout(Request $request)
    {
        $user = auth()->user();
        $affiliate = $this->affiliateService->getOrCreateAffiliateForUser($user);

        $validated = $request->validate([
            'payout_method'  => 'required|string|in:paypal,bank_transfer',
            'payout_account' => 'required|string|max:255',
        ]);

        $affiliate->update($validated);

        return redirect()->back()->with('success', 'Payout settings updated successfully.');
    }
}
