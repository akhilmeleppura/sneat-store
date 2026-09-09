<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\RfqQuote;
use Modules\Order\Services\RfqService;

class AdminRfqController extends Controller
{
    /**
     * List all B2B wholesale quotation requests.
     */
    public function index(Request $request)
    {
        $query = RfqQuote::withoutTenancy()->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'data'   => $query->paginate(15),
            ]);
        }

        $quotes = $query->paginate(15)->withQueryString();

        $stats = [
            'total'     => RfqQuote::withoutTenancy()->count(),
            'pending'   => RfqQuote::withoutTenancy()->where('status', 'pending')->count(),
            'reviewing' => RfqQuote::withoutTenancy()->where('status', 'reviewing')->count(),
            'quoted'    => RfqQuote::withoutTenancy()->where('status', 'quoted')->count(),
            'accepted'  => RfqQuote::withoutTenancy()->where('status', 'accepted')->count(),
            'rejected'  => RfqQuote::withoutTenancy()->where('status', 'rejected')->count(),
        ];

        return view('order::admin.rfq.index', compact('quotes', 'stats'));
    }

    /**
     * Display RFQ details and item breakdown.
     */
    public function show(int $id)
    {
        $quote = RfqQuote::withoutTenancy()->findOrFail($id);

        return view('order::admin.rfq.show', compact('quote'));
    }

    /**
     * Submit / Update formal quotation pricing.
     */
    public function submitProposal(Request $request, int $id, RfqService $rfqService)
    {
        $validated = $request->validate([
            'quoted_total' => 'required|numeric|min:0',
            'valid_days'   => 'nullable|integer|min:1|max:90',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $validDays = $validated['valid_days'] ?? 14;
        $quote = $rfqService->quotePrice($id, (float) $validated['quoted_total'], $validDays);

        if (!empty($validated['notes'])) {
            $quote->update(['notes' => $validated['notes']]);
        }

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status'  => 'success',
                'message' => "Quotation proposal sent for {$quote->quote_number}.",
                'quote'   => $quote,
            ]);
        }

        return redirect()->route('admin.rfq.show', $quote->id)
            ->with('success', "Quotation proposal of $" . number_format($validated['quoted_total'], 2) . " sent for {$quote->quote_number} (valid for {$validDays} days).");
    }

    /**
     * Update quotation lifecycle status.
     */
    public function updateStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,reviewing,quoted,accepted,rejected',
        ]);

        $quote = RfqQuote::withoutTenancy()->findOrFail($id);
        $quote->update(['status' => $validated['status']]);

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status'  => 'success',
                'message' => "RFQ #{$quote->quote_number} status updated to {$quote->status}.",
            ]);
        }

        return redirect()->back()
            ->with('success', "RFQ #{$quote->quote_number} status updated to {$quote->status}.");
    }
}
