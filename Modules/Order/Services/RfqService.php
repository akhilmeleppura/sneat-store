<?php

namespace Modules\Order\Services;

use Illuminate\Support\Str;
use Modules\Order\Models\RfqQuote;

class RfqService
{
    /**
     * B2B Customer submits an RFQ inquiry.
     */
    public function submitQuote(array $data, ?int $userId = null): RfqQuote
    {
        $quoteNumber = 'RFQ-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        return RfqQuote::create([
            'user_id'       => $userId,
            'quote_number'  => $quoteNumber,
            'company_name'  => $data['company_name'],
            'contact_name'  => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'tax_id'        => $data['tax_id'] ?? null,
            'items_payload' => $data['items'] ?? [],
            'status'        => 'pending',
            'notes'         => $data['notes'] ?? null,
        ]);
    }

    /**
     * Merchant quotes pricing for B2B buyer.
     */
    public function quotePrice(int $quoteId, float $quotedTotal, int $validDays = 14): RfqQuote
    {
        $quote = RfqQuote::findOrFail($quoteId);
        $quote->update([
            'quoted_total' => $quotedTotal,
            'status'       => 'quoted',
            'valid_until'  => now()->addDays($validDays),
        ]);

        return $quote;
    }

    /**
     * Customer accepts quotation.
     */
    public function acceptQuote(int $quoteId): RfqQuote
    {
        $quote = RfqQuote::findOrFail($quoteId);
        $quote->update(['status' => 'accepted']);

        return $quote;
    }
}
