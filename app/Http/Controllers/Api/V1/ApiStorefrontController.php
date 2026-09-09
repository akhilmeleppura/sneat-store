<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cart\Services\CartService;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Context\Facades\Context;
use Modules\Context\Services\CurrencyService;
use Modules\Inventory\Services\InventoryService;
use Modules\Order\Models\Order;
use App\Models\Payments\PaymentOption;

class ApiStorefrontController extends Controller
{
    /**
     * Get paginated products with filtering and sorting.
     */
    public function products(Request $request): JsonResponse
    {
        $query = Product::where('status', 'published')
            ->with(['primaryImage', 'category:id,name,slug', 'brand:id,name,slug', 'variants']);

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('brand')) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('slug', $request->brand);
            });
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            default      => $query->latest(),
        };

        $perPage = min((int) $request->get('per_page', 12), 50);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $paginated->items(),
            'meta'   => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * Get single product details by slug.
     */
    public function product(string $slug, InventoryService $inventoryService): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'published')
            ->with(['images', 'category', 'brand', 'variants.attributeValues.attribute', 'approvedReviews.user:id,name'])
            ->first();

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Product not found.',
            ], 404);
        }

        $branchId = Context::branchId() ?? 1;
        $variantsWithStock = $product->variants->map(function ($v) use ($inventoryService, $branchId) {
            $stock = $inventoryService->getAvailableStock($v->id, $branchId);
            return array_merge($v->toArray(), ['available_stock' => $stock]);
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'product'          => $product,
                'variants'         => $variantsWithStock,
                'rating_average'   => (float) $product->rating_cache,
                'rating_count'     => (int) $product->rating_count,
            ],
        ]);
    }

    /**
     * Get all active categories.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::active()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $categories,
        ]);
    }

    /**
     * Get all active brands.
     */
    public function brands(): JsonResponse
    {
        $brands = Brand::active()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $brands,
        ]);
    }

    /**
     * Get current shopping cart contents.
     */
    public function cart(CartService $cartService): JsonResponse
    {
        $cart = $cartService->getActiveCart();

        if (!$cart) {
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'items'          => [],
                    'total_quantity' => 0,
                    'subtotal'       => 0,
                    'grand_total'    => 0,
                ],
            ]);
        }

        $cart->load(['items.variant.product']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'             => $cart->id,
                'items'          => $cart->items,
                'total_quantity' => $cart->total_quantity,
                'subtotal'       => (float) $cart->subtotal,
                'coupon_code'    => $cart->coupon_code,
                'discount_amount'=> (float) $cart->discount_amount,
                'grand_total'    => (float) $cart->grand_total,
            ],
        ]);
    }

    /**
     * Add item to active cart.
     */
    public function addToCart(Request $request, CartService $cartService): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'quantity'           => 'required|integer|min:1|max:100',
        ]);

        try {
            $item = $cartService->addItem($validated['product_variant_id'], $validated['quantity']);
            $cart = $cartService->getActiveCart();

            return response()->json([
                'status'  => 'success',
                'message' => 'Item added to cart successfully.',
                'data'    => [
                    'item'           => $item,
                    'total_quantity' => $cart ? $cart->total_quantity : $validated['quantity'],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Public order tracking via API.
     */
    public function trackOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string',
            'email'        => 'required|email',
        ]);

        $order = Order::where('order_number', trim($validated['order_number']))
            ->where('customer_email', trim($validated['email']))
            ->with(['items', 'shippingMethod', 'shipments'])
            ->first();

        if (!$order) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No order found matching the provided order number and email address.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'order_number'            => $order->order_number,
                'status'                  => $order->status,
                'fulfillment_status'      => $order->fulfillment_status,
                'payment_status'          => $order->payment_status,
                'created_at'              => $order->created_at->toISOString(),
                'estimated_delivery_date' => $order->estimated_delivery_date,
                'shipping_method'         => $order->shippingMethod?->name,
                'latest_shipment'         => $order->latestShipment,
                'items_count'             => $order->items->count(),
                'grand_total'             => (float) $order->grand_total,
            ],
        ]);
    }

    /**
     * Get store configuration, active currencies, and payment gateways.
     */
    public function storeInfo(): JsonResponse
    {
        $currencies = app(CurrencyService::class)->getActiveCurrencies();
        $currentCurrency = app(CurrencyService::class)->getCurrentCurrency();

        $paymentOptions = class_exists(PaymentOption::class)
            ? PaymentOption::where('is_active', 1)->get(['id', 'slug', 'name', 'gateway'])
            : [];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'store_name'       => config('variables.templateName', 'Sneat Store'),
                'currencies'       => $currencies,
                'current_currency' => $currentCurrency,
                'payment_options'  => $paymentOptions,
                'api_version'      => 'v1',
            ],
        ]);
    }
}
