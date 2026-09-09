<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Modules\Order\Models\Shipment;
use Modules\Order\Models\ShippingMethod;
use Modules\Order\Services\ShippingService;

class ShipmentController extends Controller
{
    public function __construct(protected ShippingService $shippingService)
    {
    }

    /**
     * Display a listing of shipments with tracking status filters and KPIs.
     */
    public function index(Request $request)
    {
        $query = Shipment::with(['order', 'shippingMethod'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('carrier', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($oq) use ($search) {
                      $oq->where('order_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('carrier')) {
            $query->where('carrier', $request->carrier);
        }

        $shipments = $query->paginate(15)->withQueryString();

        // Fulfillment KPIs
        $totalShipments = Shipment::count();
        $pendingShipments = Shipment::whereIn('status', [Shipment::STATUS_PENDING, Shipment::STATUS_PROCESSING])->count();
        $inTransitShipments = Shipment::whereIn('status', [Shipment::STATUS_DISPATCHED, Shipment::STATUS_IN_TRANSIT, Shipment::STATUS_OUT_FOR_DELIVERY])->count();
        $deliveredShipments = Shipment::where('status', Shipment::STATUS_DELIVERED)->count();
        $carriersList = Shipment::distinct()->pluck('carrier')->filter();

        return view('order::admin.shipments.index', compact(
            'shipments',
            'totalShipments',
            'pendingShipments',
            'inTransitShipments',
            'deliveredShipments',
            'carriersList'
        ));
    }

    /**
     * Display detailed shipment tracking and milestone progress.
     */
    public function show(int $id)
    {
        $shipment = Shipment::with(['order.items.variant.product', 'shippingMethod'])->findOrFail($id);

        $availableCarriers = ShippingMethod::where('is_active', true)->distinct()->pluck('carrier')->filter();

        return view('order::admin.shipments.show', compact('shipment', 'availableCarriers'));
    }

    /**
     * Dispatch shipment package with carrier and tracking details.
     */
    public function dispatchShipment(Request $request, int $id)
    {
        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'carrier'         => 'required|string|max:100',
            'tracking_number' => 'required|string|max:100',
            'tracking_url'    => 'nullable|url|max:500',
            'notes'           => 'nullable|string|max:500',
        ]);

        if (! empty($validated['notes'])) {
            $shipment->notes = $validated['notes'];
            $shipment->save();
        }

        $this->shippingService->updateShipmentStatus(
            $shipment,
            Shipment::STATUS_DISPATCHED,
            "Package dispatched via {$validated['carrier']} with tracking #{$validated['tracking_number']}.",
            'Fulfillment Center',
            $validated['tracking_number'],
            $validated['carrier'],
            $validated['tracking_url'] ?? null
        );

        return redirect()->back()->with('success', "Shipment {$shipment->shipment_number} dispatched successfully.");
    }

    /**
     * Update shipment milestone and status.
     */
    public function updateStatus(Request $request, int $id)
    {
        $shipment = Shipment::findOrFail($id);

        $validated = $request->validate([
            'status'          => 'required|in:pending,processing,dispatched,in_transit,out_for_delivery,delivered,failed,returned',
            'description'     => 'nullable|string|max:500',
            'location'        => 'nullable|string|max:150',
            'tracking_number' => 'nullable|string|max:100',
            'carrier'         => 'nullable|string|max:100',
            'tracking_url'    => 'nullable|url|max:500',
            'notes'           => 'nullable|string|max:500',
        ]);

        if (! empty($validated['notes'])) {
            $shipment->notes = $validated['notes'];
            $shipment->save();
        }

        $this->shippingService->updateShipmentStatus(
            $shipment,
            $validated['status'],
            $validated['description'] ?? null,
            $validated['location'] ?? null,
            $validated['tracking_number'] ?? null,
            $validated['carrier'] ?? null,
            $validated['tracking_url'] ?? null
        );

        return redirect()->back()->with('success', "Shipment status updated to " . ucfirst(str_replace('_', ' ', $validated['status'])) . ".");
    }

    /**
     * Create a shipment for an existing order from order management.
     */
    public function createForOrder(Request $request, int $orderId)
    {
        $order = Order::findOrFail($orderId);

        $validated = $request->validate([
            'carrier'               => 'nullable|string|max:100',
            'tracking_number'       => 'nullable|string|max:100',
            'tracking_url'          => 'nullable|url|max:500',
            'shipping_method_id'    => 'nullable|exists:shipping_methods,id',
            'estimated_delivery_at' => 'nullable|date',
            'notes'                 => 'nullable|string|max:500',
        ]);

        $shipment = $this->shippingService->createShipment($order, $validated);

        // If tracking info was provided, immediately mark dispatched
        if (! empty($validated['tracking_number'])) {
            $shipment->markAsDispatched(
                $validated['tracking_number'],
                $validated['carrier'] ?? null,
                $validated['tracking_url'] ?? null
            );
        }

        return redirect()->route('admin.shipments.show', $shipment->id)
            ->with('success', "Shipment {$shipment->shipment_number} created for order {$order->order_number}.");
    }
}
