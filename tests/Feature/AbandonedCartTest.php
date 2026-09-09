<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Cart\Models\AbandonedCartRecovery;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Cart\Services\AbandonedCartService;
use Modules\Catalog\Models\ProductVariant;
use Modules\Order\Models\Order;
use Tests\TestCase;

class AbandonedCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_detect_abandoned_carts_and_create_recovery_record()
    {
        $user = User::first() ?: User::factory()->create();
        $variant = ProductVariant::first();

        $uniqueEmail = 'shopper.' . uniqid() . '@example.com';

        // 1. Create cart updated 2 hours ago
        $cart = Cart::create([
            'tenant_id'      => 1,
            'store_id'       => 1,
            'user_id'        => null,
            'cart_token'     => Str::random(32),
            'currency'       => 'USD',
            'customer_email' => $uniqueEmail,
        ]);

        CartItem::create([
            'cart_id'            => $cart->id,
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
            'unit_price'         => 50.00,
        ]);

        // Artificially age the cart using direct DB update
        Cart::withoutTenancy()->where('id', $cart->id)->update(['updated_at' => now()->subHours(3)]);

        $service = app(AbandonedCartService::class);
        $detected = $service->detectAbandonedCarts(1);

        $this->assertEquals(1, $detected);
        $this->assertDatabaseHas('abandoned_cart_recoveries', [
            'cart_id'        => $cart->id,
            'customer_email' => $uniqueEmail,
            'status'         => 'pending',
        ]);
    }

    public function test_artisan_command_detects_carts()
    {
        $user = User::first() ?: User::factory()->create();
        $variant = ProductVariant::first();

        $cart = Cart::create([
            'tenant_id'      => 1,
            'store_id'       => 1,
            'user_id'        => $user->id,
            'cart_token'     => Str::random(32),
            'customer_email' => 'artisan.cart@test.com',
        ]);

        CartItem::create([
            'cart_id'            => $cart->id,
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
            'unit_price'         => 80.00,
        ]);

        Cart::withoutTenancy()->where('id', $cart->id)->update(['updated_at' => now()->subHours(3)]);

        $this->artisan('cart:detect-abandoned', ['--hours' => 1])
            ->assertExitCode(0);

        $this->assertDatabaseHas('abandoned_cart_recoveries', [
            'customer_email' => 'artisan.cart@test.com',
        ]);
    }

    public function test_one_click_recovery_restores_cart_session()
    {
        $user = User::first() ?: User::factory()->create();
        $variant = ProductVariant::first();

        $cart = Cart::create([
            'tenant_id'      => 1,
            'store_id'       => 1,
            'user_id'        => $user->id,
            'cart_token'     => 'RECOVERY-TOKEN-CART-TEST',
            'customer_email' => $user->email,
        ]);

        CartItem::create([
            'cart_id'            => $cart->id,
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
            'unit_price'         => 120.00,
        ]);

        $recovery = AbandonedCartRecovery::create([
            'tenant_id'              => 1,
            'cart_id'                => $cart->id,
            'user_id'                => $user->id,
            'customer_email'         => $user->email,
            'cart_subtotal'          => 120.00,
            'recovery_token'         => 'TEST-TOKEN-12345',
            'status'                 => 'pending',
            'recovery_discount_code' => 'COMEBACK10',
            'items_count'            => 1,
        ]);

        $response = $this->get('/cart/recover/TEST-TOKEN-12345');

        $response->assertRedirect(route('store.checkout.index'));
        $response->assertSessionHas('cart_token', 'RECOVERY-TOKEN-CART-TEST');
        $this->assertEquals('COMEBACK10', $cart->fresh()->coupon_code);
    }

    public function test_cart_marked_as_recovered_by_service()
    {
        $user = User::first() ?: User::factory()->create();
        $cart = Cart::create([
            'tenant_id'      => 1,
            'store_id'       => 1,
            'user_id'        => $user->id,
            'cart_token'     => Str::random(32),
            'customer_email' => $user->email,
        ]);

        $recovery = AbandonedCartRecovery::create([
            'tenant_id'      => 1,
            'cart_id'        => $cart->id,
            'customer_email' => $user->email,
            'cart_subtotal'  => 100.00,
            'recovery_token' => 'RECOVER-ME-TOKEN',
            'status'         => 'pending',
            'items_count'    => 1,
        ]);

        $order = Order::create([
            'tenant_id'       => 1,
            'order_number'    => 'TEST-REC-ORD-1',
            'store_id'        => 1,
            'user_id'         => $user->id,
            'customer_name'   => $user->name,
            'customer_email'  => $user->email,
            'subtotal'        => 100.00,
            'grand_total'     => 100.00,
            'status'          => 'completed',
            'payment_status'  => 'paid',
            'payment_method'  => 'cod',
        ]);

        $service = app(AbandonedCartService::class);
        $service->markAsRecovered($cart, $order);

        $recovery->refresh();
        $this->assertEquals('recovered', $recovery->status);
        $this->assertEquals($order->id, $recovery->recovered_order_id);
    }

    public function test_admin_abandoned_carts_index()
    {
        $user = User::first() ?: User::factory()->create();
        $response = $this->actingAs($user)->get(route('admin.abandoned_carts.index'));

        $response->assertStatus(200);
        $response->assertSee('Abandoned Carts');
        $response->assertSee('Recoverable Value');
    }
}
