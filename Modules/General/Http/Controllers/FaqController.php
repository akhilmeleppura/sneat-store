<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Display Help Center & FAQ page.
     */
    public function index()
    {
        $faqs = [
            'Orders & Shipping' => [
                ['q' => 'How can I track my shipment?', 'a' => 'You can track any active shipment without logging in using our public tracking page (/track-order) with your Order Number and Email Address.'],
                ['q' => 'What shipping carriers do you use?', 'a' => 'We partner with premier global carriers including FedEx, DHL Express, UPS, and local fulfillment fleets for same-day or standard delivery.'],
                ['q' => 'Can I pick up my order in-store?', 'a' => 'Yes! Select "Fulfillment & Store Pickup" during checkout or use our interactive Store Locator map to pick up at any of our retail branches.'],
            ],
            'Payments & Security' => [
                ['q' => 'What payment options are supported?', 'a' => 'We accept Credit/Debit cards via Stripe, PayPal Express Checkout, Cash on Delivery (COD), and Direct Bank Wire Transfers.'],
                ['q' => 'Are my card details secure?', 'a' => 'Yes, our platform is PCI-DSS compliant. Raw credit card numbers are never stored on our servers; payments are tokenized securely through verified gateway vaults.'],
            ],
            'Returns & Refunds (RMA)' => [
                ['q' => 'What is your return policy?', 'a' => 'Items in original condition can be returned within 30 days of delivery. You can initiate a Return Merchandise Authorization (RMA) directly from your customer account dashboard.'],
                ['q' => 'How long do refunds take to process?', 'a' => 'Once our warehouse inspects the returned parcel, refunds are processed within 3-5 business days back to your original payment method.'],
            ],
            'Loyalty & Gift Cards' => [
                ['q' => 'How do I earn loyalty points?', 'a' => 'Every registered customer automatically earns 1 point per $1 spent on orders. Points can be redeemed for instant discount savings during checkout.'],
                ['q' => 'How do I check my gift card balance?', 'a' => 'Enter your 16-character gift card code during checkout or check your balance anytime via our Gift Card Checker.'],
            ],
        ];

        return view('general::faq', compact('faqs'));
    }
}
