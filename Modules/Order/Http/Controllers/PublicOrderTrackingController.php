<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\Order;

class PublicOrderTrackingController extends Controller
{
    /**
     * Public order tracking search page.
     */
    public function index()
    {
        return view('order::tracking.public');
    }

    /**
     * Search and track order progression.
     */
    public function track(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:100',
            'email'        => 'required|email|max:255',
        ]);

        $order = Order::where('order_number', trim($validated['order_number']))
            ->where('customer_email', trim($validated['email']))
            ->with(['items', 'shippingMethod', 'shipments.shippingMethod'])
            ->first();

        if (!$order) {
            return back()
                ->withInput()
                ->with('error', 'No order found matching the provided Order Number and Billing Email address. Please check your confirmation email and try again.');
        }

        $shipment = $order->latestShipment;

        return view('order::tracking.public', compact('order', 'shipment'));
    }
}
