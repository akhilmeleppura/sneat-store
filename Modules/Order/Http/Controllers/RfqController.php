<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Order\Models\RfqQuote;
use Modules\Order\Services\RfqService;

class RfqController extends Controller
{
    /**
     * Submit B2B Request for Quote.
     */
    public function store(Request $request, RfqService $rfqService): JsonResponse
    {
        $validated = $request->validate([
            'company_name'  => 'required|string|max:255',
            'contact_name'  => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'tax_id'        => 'nullable|string|max:50',
            'items'         => 'required|array|min:1',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $quote = $rfqService->submitQuote($validated, Auth::id());

        return response()->json([
            'status'       => 'success',
            'quote_number' => $quote->quote_number,
            'message'      => "Quotation request {$quote->quote_number} submitted. A corporate account representative will send pricing within 24 hours.",
        ]);
    }

    /**
     * View RFQ details.
     */
    public function show(string $quoteNumber): JsonResponse
    {
        $quote = RfqQuote::where('quote_number', $quoteNumber)->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data'   => $quote,
        ]);
    }
}
