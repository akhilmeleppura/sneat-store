<?php

namespace Modules\Cart\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Cart\Models\AbandonedCartRecovery;
use Modules\Cart\Services\AbandonedCartService;

class AbandonedCartAdminController extends Controller
{
    public function __construct(protected AbandonedCartService $service)
    {
    }

    /**
     * Display list of abandoned carts with KPIs.
     */
    public function index(Request $request)
    {
        $query = AbandonedCartRecovery::with(['cart.items.variant.product', 'user', 'recoveredOrder'])->latest();

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'reminded') {
                $query->reminded();
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('recovery_token', 'like', "%{$search}%");
            });
        }

        $recoveries = $query->paginate(15)->withQueryString();
        $stats = $this->service->getStats();

        return view('cart::admin.abandoned_carts.index', compact('recoveries', 'stats'));
    }

    /**
     * Trigger manual recovery reminder notification.
     */
    public function sendReminder($id)
    {
        $recovery = AbandonedCartRecovery::findOrFail($id);
        $sent = $this->service->sendRecoveryNotification($recovery);

        if (! $sent) {
            return redirect()->back()->with('error', 'Cannot send reminder for an already recovered cart.');
        }

        return redirect()->back()->with('success', "Recovery reminder dispatched to {$recovery->customer_email}.");
    }

    /**
     * Scan carts manually from admin UI.
     */
    public function triggerScan()
    {
        $detected = $this->service->detectAbandonedCarts(1);
        return redirect()->back()->with('success', "Scan completed. {$detected} newly abandoned carts identified.");
    }
}
