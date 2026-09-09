<?php

namespace Modules\Catalog\Services;

use Modules\Catalog\Models\BackInStockSubscription;
use Modules\Catalog\Models\Product;

class BackInStockService
{
    /**
     * Subscribe customer email to out-of-stock notification.
     */
    public function subscribe(int $productId, string $email, ?string $phone = null, ?int $variantId = null): array
    {
        $existing = BackInStockSubscription::where('product_id', $productId)
            ->where('email', $email)
            ->where('is_notified', false)
            ->first();

        if ($existing) {
            return [
                'status'  => 'info',
                'message' => 'You are already subscribed to back-in-stock alerts for this product.',
            ];
        }

        BackInStockSubscription::create([
            'product_id'  => $productId,
            'variant_id'  => $variantId,
            'email'       => $email,
            'phone'       => $phone,
            'is_notified' => false,
        ]);

        return [
            'status'  => 'success',
            'message' => "You'll be notified via email as soon as this item is restocked!",
        ];
    }

    /**
     * Notify subscribers when stock arrives.
     */
    public function notifyRestocked(int $productId): int
    {
        $subscriptions = BackInStockSubscription::withoutTenancy()
            ->where('product_id', $productId)
            ->where('is_notified', false)
            ->get();

        foreach ($subscriptions as $sub) {
            $sub->update([
                'is_notified' => true,
                'notified_at' => now(),
            ]);
            // In production, triggers queued Mailable / SMS
        }

        return $subscriptions->count();
    }
}
