<?php

namespace Modules\Marketplace\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Models\VendorPayout;

class AdminVendorController extends Controller
{
    /**
     * Display all marketplace vendors.
     */
    public function index(Request $request)
    {
        $query = Vendor::with('user')->withCount('products')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $vendors = $query->paginate(15)->withQueryString();

        return view('marketplace::admin.vendors.index', compact('vendors'));
    }

    /**
     * Update vendor status and commission rate.
     */
    public function updateStatus(Request $request, int $id)
    {
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'status'          => 'required|in:active,pending,suspended',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $vendor->update($validated);

        return redirect()->back()
            ->with('success', "Vendor '{$vendor->name}' updated successfully.");
    }

    /**
     * Display payout requests across vendors.
     */
    public function payouts(Request $request)
    {
        $query = VendorPayout::with('vendor')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->paginate(15)->withQueryString();

        return view('marketplace::admin.payouts.index', compact('payouts'));
    }

    /**
     * Approve or reject a payout request.
     */
    public function handlePayoutAction(Request $request, int $id)
    {
        $payout = VendorPayout::with('vendor')->findOrFail($id);

        $validated = $request->validate([
            'action'                => 'required|in:approve,reject',
            'transaction_reference' => 'nullable|string|max:100',
            'notes'                 => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($payout, $validated) {
            if ($validated['action'] === 'approve') {
                // Deduct from vendor balance if not already deducted
                if ($payout->status === 'requested') {
                    $payout->vendor->decrement('balance', $payout->amount);
                }

                $payout->update([
                    'status'                => 'completed',
                    'transaction_reference' => $validated['transaction_reference'] ?? ('wire_' . rand(100000, 999999)),
                    'processed_at'          => now(),
                    'notes'                 => $validated['notes'] ?? $payout->notes,
                ]);
            } else {
                $payout->update([
                    'status' => 'rejected',
                    'notes'  => $validated['notes'] ?? 'Payout rejected by administrator.',
                ]);
            }
        });

        if ($validated['action'] === 'approve') {
            event(new \Modules\Marketplace\Events\VendorPayoutApprovedEvent($payout));
        }

        return redirect()->back()
            ->with('success', "Payout #{$payout->id} status updated to {$payout->status}.");
    }
}
