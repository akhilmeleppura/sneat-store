<?php

namespace Modules\Order\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;

class Shipment extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'shipments';

    public const STATUS_PENDING          = 'pending';
    public const STATUS_PROCESSING       = 'processing';
    public const STATUS_DISPATCHED       = 'dispatched';
    public const STATUS_IN_TRANSIT       = 'in_transit';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED        = 'delivered';
    public const STATUS_FAILED           = 'failed';
    public const STATUS_RETURNED         = 'returned';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'shipping_method_id',
        'shipment_number',
        'tracking_number',
        'carrier',
        'tracking_url',
        'status',
        'shipped_at',
        'estimated_delivery_at',
        'delivered_at',
        'recipient_name',
        'delivery_address',
        'timeline',
        'notes',
    ];

    protected $casts = [
        'delivery_address'      => 'array',
        'timeline'              => 'array',
        'shipped_at'            => 'datetime',
        'estimated_delivery_at' => 'datetime',
        'delivered_at'          => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'deleted_at'            => 'datetime',
    ];

    /**
     * Parent order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Shipping carrier/method used.
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    /**
     * Badge CSS class for Sneat UI.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING          => 'bg-label-secondary',
            self::STATUS_PROCESSING       => 'bg-label-info',
            self::STATUS_DISPATCHED       => 'bg-label-primary',
            self::STATUS_IN_TRANSIT       => 'bg-label-warning',
            self::STATUS_OUT_FOR_DELIVERY => 'bg-label-info',
            self::STATUS_DELIVERED        => 'bg-label-success',
            self::STATUS_FAILED           => 'bg-label-danger',
            self::STATUS_RETURNED         => 'bg-label-dark',
            default                       => 'bg-label-secondary',
        };
    }

    /**
     * User-friendly status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING          => 'Pending',
            self::STATUS_PROCESSING       => 'Processing',
            self::STATUS_DISPATCHED       => 'Dispatched',
            self::STATUS_IN_TRANSIT       => 'In Transit',
            self::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            self::STATUS_DELIVERED        => 'Delivered',
            self::STATUS_FAILED           => 'Failed Attempt',
            self::STATUS_RETURNED         => 'Returned to Origin',
            default                       => ucfirst($this->status),
        };
    }

    /**
     * Append a tracking milestone event to timeline array.
     */
    public function addTimelineEvent(string $status, string $description, ?string $location = null, ?string $timestamp = null): void
    {
        $timeline = $this->timeline ?? [];

        $timeline[] = [
            'status'      => $status,
            'description' => $description,
            'location'    => $location,
            'timestamp'   => $timestamp ?: Carbon::now()->toIso8601String(),
        ];

        $this->timeline = $timeline;
        $this->save();
    }

    /**
     * Transition shipment to dispatched.
     */
    public function markAsDispatched(?string $trackingNumber = null, ?string $carrier = null, ?string $trackingUrl = null): void
    {
        $this->status = self::STATUS_DISPATCHED;
        $this->shipped_at = Carbon::now();

        if ($trackingNumber) {
            $this->tracking_number = $trackingNumber;
        }
        if ($carrier) {
            $this->carrier = $carrier;
        }
        if ($trackingUrl) {
            $this->tracking_url = $trackingUrl;
        }

        $this->addTimelineEvent(
            self::STATUS_DISPATCHED,
            "Package dispatched via " . ($this->carrier ?: 'carrier') . ($this->tracking_number ? " (Tracking: {$this->tracking_number})" : ""),
            'Fulfillment Center'
        );

        // Synchronize parent Order fulfillment status
        if ($this->order) {
            $this->order->update([
                'fulfillment_status' => 'fulfilled',
                'status'             => ($this->order->status === 'pending') ? 'processing' : $this->order->status,
            ]);
        }
    }

    /**
     * Transition shipment to delivered.
     */
    public function markAsDelivered(?string $notes = null): void
    {
        $this->status = self::STATUS_DELIVERED;
        $this->delivered_at = Carbon::now();

        if ($notes) {
            $this->notes = $notes;
        }

        $this->addTimelineEvent(
            self::STATUS_DELIVERED,
            "Shipment successfully delivered to recipient.",
            $this->delivery_address['city'] ?? 'Destination'
        );

        if ($this->order) {
            $this->order->update([
                'fulfillment_status' => 'fulfilled',
                'status'             => 'completed',
            ]);
        }
    }
}
