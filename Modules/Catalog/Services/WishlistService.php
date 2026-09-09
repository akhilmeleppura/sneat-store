<?php

namespace Modules\Catalog\Services;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Wishlist;
use Modules\Cart\Services\CartService;
use Modules\Context\Facades\Context;

class WishlistService
{
    /**
     * Toggle product in user's wishlist.
     */
    public function toggle(int $userId, int $productId): array
    {
        $tenantId = Context::tenantId();

        $existing = Wishlist::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            $action = 'removed';
            $inWishlist = false;
        } else {
            Wishlist::create([
                'tenant_id'  => $tenantId,
                'user_id'    => $userId,
                'product_id' => $productId,
            ]);
            $action = 'added';
            $inWishlist = true;
        }

        $count = $this->getWishlistCount($userId);

        return [
            'status'      => 'success',
            'action'      => $action,
            'in_wishlist' => $inWishlist,
            'count'       => $count,
            'message'     => $action === 'added' ? 'Product added to your wishlist!' : 'Product removed from your wishlist.',
        ];
    }

    /**
     * Check if a product is in the user's wishlist.
     */
    public function isInWishlist(?int $userId, int $productId): bool
    {
        if (!$userId) {
            return false;
        }

        return Wishlist::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Get count of items in user's wishlist.
     */
    public function getWishlistCount(?int $userId): int
    {
        if (!$userId) {
            return 0;
        }

        return Wishlist::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Get user's wishlist with loaded products and variants.
     */
    public function getUserWishlist(int $userId)
    {
        return Wishlist::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->with(['product.primaryImage', 'product.variants', 'product.category', 'product.brand'])
            ->latest()
            ->get();
    }

    /**
     * Move wishlist item directly into shopping cart.
     */
    public function moveToCart(int $userId, int $wishlistId, ?CartService $cartService = null): array
    {
        $wishlist = Wishlist::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('id', $wishlistId)
            ->with('product.variants')
            ->firstOrFail();

        $product = $wishlist->product;
        if (!$product) {
            $wishlist->delete();
            return ['status' => 'error', 'message' => 'Product is no longer available.'];
        }

        $variant = $product->variants->first();
        if (!$variant) {
            return ['status' => 'error', 'message' => 'Product variant not found.'];
        }

        $cartService = $cartService ?: app(CartService::class);
        $cartService->addItem($variant->id, 1);

        $wishlist->delete();

        return [
            'status'  => 'success',
            'message' => "Moved {$product->name} to your shopping cart!",
            'count'   => $this->getWishlistCount($userId),
        ];
    }
}
