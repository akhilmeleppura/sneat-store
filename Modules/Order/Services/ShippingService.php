<?php

namespace Modules\Order\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Cart\Models\Cart;
use Modules\Context\Facades\Context;
use Modules\Order\Models\Order;
use Modules\Order\Models\Shipment;
use Modules\Order\Models\ShippingMethod;

class ShippingService
{
    /**
     * Retrieve available shipping carriers/methods for a given Cart.
     */
    public function getAvailableMethods(?Cart $cart = null, float $subtotal = 0.0): Collection
    {
        $tenantId = $cart?->tenant_id ?? (Context::tenantId() ?? 1);
        $storeId  = $cart?->store_id ?? Context::storeId();

        $query = ShippingMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($storeId, function ($q) use ($storeId) {
                $q->where(function ($sq) use ($storeId) {
                    $sq->where('store_id', $storeId)
                       ->orWhereNull('store_id');
                });
            })
            ->orderBy('sort_order', 'asc');

        $methods = $query->get();

        // Calculate total weight of cart
        $weight = $this->calculateCartWeight($cart);

        // Pre-compute rate for each method
        foreach ($methods as $method) {
            $method->calculated_rate = $method->calculateRate($subtotal, $weight);
            $method->delivery_date = $method->calculateEstimatedDeliveryDate();
        }

        return $methods;
    }

    /**
     * Compute shipping cost for a given method and cart.
     */
    public function calculateCost(?ShippingMethod $method, ?Cart $cart = null, float $subtotal = 0.0): float
    {
        if (! $method) {
            $defaultFee = (float) config('order.default_shipping_fee', 0.00);
            $freeThreshold = (float) config('order.free_shipping_threshold', 100.00);
            return ($subtotal >= $freeThreshold) ? 0.00 : $defaultFee;
        }

        $weight = $this->calculateCartWeight($cart);

        return $method->calculateRate($subtotal, $weight);
    }

    /**
     * Calculate parcel weight from cart items.
     */
    public function calculateCartWeight(?Cart $cart = null): float
    {
        if (! $cart || $cart->items->isEmpty()) {
            return 0.5; // default 0.5 kg
        }

        $totalWeight = 0.0;
        foreach ($cart->items as $item) {
            // Check variant weight or default to 0.5 per unit
            $itemWeight = (float) ($item->variant->weight ?? 0.5);
            $totalWeight += ($itemWeight * $item->quantity);
        }

        return max(0.1, $totalWeight);
    }

    /**
     * Create a new shipment fulfillment record for an order.
     */
    public function createShipment(Order $order, array $data = []): Shipment
    {
        $tenantId = $order->tenant_id ?? (Context::tenantId() ?? 1);
        $methodId = $data['shipping_method_id'] ?? null;
        $shippingMethod = $order->shippingMethod ?? ($methodId ? ShippingMethod::find($methodId) : null);

        $shipmentNumber = 'SHP-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        $carrier = $data['carrier'] ?? ($shippingMethod?->carrier ?? 'Standard Carrier');
        $trackingNumber = $data['tracking_number'] ?? null;
        $trackingUrl = $data['tracking_url'] ?? null;

        $estimatedDeliveryAt = null;
        if (! empty($data['estimated_delivery_at'])) {
            $estimatedDeliveryAt = Carbon::parse($data['estimated_delivery_at']);
        } elseif ($shippingMethod) {
            $estimatedDeliveryAt = $shippingMethod->calculateEstimatedDeliveryDate();
        }

        $initialTimeline = [
            [
                'status'      => Shipment::STATUS_PENDING,
                'description' => 'Shipment package created and awaiting fulfillment.',
                'location'    => 'Main Fulfillment Center',
                'timestamp'   => Carbon::now()->toIso8601String(),
            ],
        ];

        $shipment = Shipment::create([
            'tenant_id'             => $tenantId,
            'order_id'              => $order->id,
            'shipping_method_id'    => $shippingMethod?->id,
            'shipment_number'       => $shipmentNumber,
            'tracking_number'       => $trackingNumber,
            'carrier'               => $carrier,
            'tracking_url'          => $trackingUrl,
            'status'                => Shipment::STATUS_PENDING,
            'estimated_delivery_at' => $estimatedDeliveryAt,
            'recipient_name'        => $order->customer_name,
            'delivery_address'      => $order->shipping_address,
            'timeline'              => $initialTimeline,
            'notes'                 => $data['notes'] ?? null,
        ]);

        return $shipment;
    }

    /**
     * Update shipment status and milestone timeline.
     */
    public function updateShipmentStatus(
        Shipment $shipment,
        string $status,
        ?string $description = null,
        ?string $location = null,
        ?string $trackingNumber = null,
        ?string $carrier = null,
        ?string $trackingUrl = null
    ): Shipment {
        if ($carrier) {
            $shipment->carrier = $carrier;
        }
        if ($trackingNumber) {
            $shipment->tracking_number = $trackingNumber;
        }
        if ($trackingUrl) {
            $shipment->tracking_url = $trackingUrl;
        }

        if ($status === Shipment::STATUS_DISPATCHED) {
            $shipment->markAsDispatched($trackingNumber, $carrier, $trackingUrl);
            return $shipment->fresh();
        }

        if ($status === Shipment::STATUS_DELIVERED) {
            $shipment->markAsDelivered($description);
            return $shipment->fresh();
        }

        $shipment->status = $status;
        $shipment->save();

        $defaultDesc = match ($status) {
            Shipment::STATUS_PROCESSING       => 'Shipment is currently being packed and prepared.',
            Shipment::STATUS_IN_TRANSIT       => 'Shipment is currently in transit with carrier.',
            Shipment::STATUS_OUT_FOR_DELIVERY => 'Shipment is out for delivery with local courier.',
            Shipment::STATUS_FAILED           => 'Delivery attempt failed. Courier will re-attempt.',
            Shipment::STATUS_RETURNED         => 'Package returned to dispatch warehouse.',
            default                           => "Status updated to {$status}.",
        };

        $shipment->addTimelineEvent(
            $status,
            $description ?: $defaultDesc,
            $location ?: 'Transit Hub'
        );

        return $shipment->fresh();
    }
}
