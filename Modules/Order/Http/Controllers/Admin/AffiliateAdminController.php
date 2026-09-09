<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\Affiliate;
use Modules\Order\Models\AffiliateReferral;
use Modules\Order\Services\AffiliateService;

class AffiliateAdminController extends Controller
{
    public function __construct(protected AffiliateService $affiliateService)
    {
    }

    /**
     * Display listing of affiliates with KPIs.
     */
    public function index(Request $request)
    {
        $query = Affiliate::withoutTenancy()->with(['user', 'referrals'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('affiliate_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $affiliates = $query->paginate(15)->withQueryString();
        $stats = $this->affiliateService->getAdminStats();

        return view('order::admin.affiliates.index', compact('affiliates', 'stats'));
    }

    /**
     * Mark referral commission as paid.
     */
    public function markPaid($referralId)
    {
        $referral = AffiliateReferral::withoutTenancy()->findOrFail($referralId);
        $this->affiliateService->markReferralPaid($referral);

        return redirect()->back()->with('success', 'Referral commission marked as paid.');
    }

    /**
     * Update custom commission rate for an affiliate.
     */
    public function updateRate(Request $request, $id)
    {
        $affiliate = Affiliate::withoutTenancy()->findOrFail($id);

        $validated = $request->validate([
            'commission_rate' => 'required|numeric|min:0.5|max:50',
        ]);

        $affiliate->update($validated);

        return redirect()->back()->with('success', "Commission rate updated to {$validated['commission_rate']}% for {$affiliate->affiliate_code}.");
    }

    /**
     * Toggle affiliate status.
     */
    public function toggleStatus($id)
    {
        $affiliate = Affiliate::withoutTenancy()->findOrFail($id);
        $affiliate->status = $affiliate->status === 'active' ? 'paused' : 'active';
        $affiliate->save();

        return redirect()->back()->with('success', "Affiliate status updated to {$affiliate->status}.");
    }
}
