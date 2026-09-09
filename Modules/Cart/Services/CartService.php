<?php

namespace Modules\Cart\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Modules\Catalog\Models\ProductVariant;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Context\Facades\Context;
use Modules\Inventory\Services\InventoryService;

class CartService
{
    protected ?Cart $activeCart = null;

    /**
     * Resolve or create the active cart for the current session or authenticated user.
     */
    public function getActiveCart(): Cart
    {
        if ($this->activeCart) {
            return $this->activeCart;
        }

        $user = Auth::user();
        $token = request()->cookie(config('cart.cookie_name', 'sneat_cart_token'))
            ?? Session::get('cart_token');

        $tenantId = Context::tenantId() ?? 1;
        $storeId = Context::storeId() ?? 1;

        if ($user) {
            $cart = Cart::where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->first();

            if (! $cart) {
                $cart = Cart::create([
                    'tenant_id'  => $tenantId,
                    'store_id'   => $storeId,
                    'user_id'    => $user->id,
                    'cart_token' => Str::random(40),
                    'currency'   => 'USD',
                ]);
            }

            // If a guest token exists, merge it into this user's cart
            if ($token && $cart->cart_token !== $token) {
                $this->mergeGuestCartIntoUser($user, $token);
            }

            $this->activeCart = $cart;
            return $cart;
        }

        // Anonymous Guest Cart
        if (! $token) {
            $token = Str::random(40);
            Cookie::queue(config('cart.cookie_name', 'sneat_cart_token'), $token, config('cart.cookie_lifetime', 43200));
            Session::put('cart_token', $token);
        }

        $cart = Cart::where('tenant_id', $tenantId)
            ->where('cart_token', $token)
            ->first();

        if (! $cart) {
            $cart = Cart::create([
                'tenant_id'  => $tenantId,
                'store_id'   => $storeId,
                'user_id'    => null,
                'cart_token' => $token,
                'currency'   => 'USD',
            ]);
        }

        $this->activeCart = $cart;
        return $cart;
    }

    /**
     * Add a product variant to the cart with real-time available stock validation.
     *
     * @throws Exception
     */
    public function addItem(int $variantId, int $quantity = 1, ?array $options = null): CartItem
    {
        $cart = $this->getActiveCart();
        $variant = ProductVariant::with('product')->findOrFail($variantId);

        // Real-time stock validation via InventoryService
        $inventoryService = app(InventoryService::class);
        $availableStock = $inventoryService->getAvailableStock($variantId);

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->first();

        $newQty = $existingItem ? ($existingItem->quantity + $quantity) : $quantity;

        if ($availableStock > 0 && $newQty > $availableStock && ! config('inventory.allow_backorders', false)) {
            throw new Exception("Only {$availableStock} units of '{$variant->product->name}' available in stock.");
        }

        $maxAllowed = config('cart.max_item_quantity', 99);
        if ($newQty > $maxAllowed) {
            $newQty = $maxAllowed;
        }

        if ($existingItem) {
            $existingItem->quantity = $newQty;
            $existingItem->save();
            return $existingItem;
        }

        return CartItem::create([
            'cart_id'            => $cart->id,
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variantId,
            'quantity'           => $newQty,
            'custom_options'     => $options,
        ]);
    }

    /**
     * Update item quantity in the cart.
     *
     * @throws Exception
     */
    public function updateQuantity(int $cartItemId, int $quantity): ?CartItem
    {
        $cart = $this->getActiveCart();
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($cartItemId);

        if ($quantity <= 0) {
            $item->delete();
            return null;
        }

        // Validate stock
        $inventoryService = app(InventoryService::class);
        $availableStock = $inventoryService->getAvailableStock($item->product_variant_id);

        if ($availableStock > 0 && $quantity > $availableStock && ! config('inventory.allow_backorders', false)) {
            throw new Exception("Cannot exceed {$availableStock} available units in stock.");
        }

        $item->quantity = min($quantity, config('cart.max_item_quantity', 99));
        $item->save();

        return $item;
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $cartItemId): bool
    {
        $cart = $this->getActiveCart();
        return (bool) CartItem::where('cart_id', $cart->id)->where('id', $cartItemId)->delete();
    }

    /**
     * Clear all items from the active cart.
     */
    public function clearCart(): void
    {
        $cart = $this->getActiveCart();
        $cart->items()->delete();
    }

    /**
     * Merge guest cart into user account cart upon login.
     */
    public function mergeGuestCartIntoUser(User $user, string $guestToken): void
    {
        $guestCart = Cart::where('cart_token', $guestToken)->whereNull('user_id')->first();

        if (! $guestCart || $guestCart->items()->count() === 0) {
            return;
        }

        $userCart = Cart::firstOrCreate(
            ['tenant_id' => $guestCart->tenant_id, 'user_id' => $user->id],
            [
                'store_id'   => $guestCart->store_id,
                'cart_token' => Str::random(40),
                'currency'   => $guestCart->currency,
            ]
        );

        foreach ($guestCart->items as $guestItem) {
            $existing = CartItem::where('cart_id', $userCart->id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            if ($existing) {
                $existing->quantity += $guestItem->quantity;
                $existing->save();
            } else {
                $guestItem->cart_id = $userCart->id;
                $guestItem->save();
            }
        }

        $guestCart->delete();
        Cookie::queue(Cookie::forget(config('cart.cookie_name', 'sneat_cart_token')));
        Session::forget('cart_token');
    }
}
