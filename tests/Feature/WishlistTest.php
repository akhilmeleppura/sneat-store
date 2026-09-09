<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Wishlist;
use Modules\Catalog\Services\WishlistService;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_guest_cannot_toggle_wishlist_via_ajax()
    {
        $product = Product::first();

        $response = $this->postJson('/store/wishlist/toggle', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(401)
            ->assertJson(['status' => 'unauthenticated']);
    }

    public function test_customer_can_add_and_remove_from_wishlist()
    {
        $customer = User::first() ?: User::create([
            'name'     => 'Jane Customer',
            'email'    => 'jane@sneat.test',
            'password' => bcrypt('secret123'),
        ]);
        $product = Product::first();

        // 1. Add to wishlist
        $response = $this->actingAs($customer)->postJson('/store/wishlist/toggle', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'      => 'success',
                'action'      => 'added',
                'in_wishlist' => true,
                'count'       => 1,
            ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id'    => $customer->id,
            'product_id' => $product->id,
        ]);

        // 2. Toggle again to remove
        $response2 = $this->actingAs($customer)->postJson('/store/wishlist/toggle', [
            'product_id' => $product->id,
        ]);

        $response2->assertStatus(200)
            ->assertJson([
                'status'      => 'success',
                'action'      => 'removed',
                'in_wishlist' => false,
                'count'       => 0,
            ]);

        $this->assertDatabaseMissing('wishlists', [
            'user_id'    => $customer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_customer_can_view_wishlist_page()
    {
        $customer = User::first() ?: User::create([
            'name'     => 'Jane Customer',
            'email'    => 'jane@sneat.test',
            'password' => bcrypt('secret123'),
        ]);
        $product = Product::first();

        Wishlist::create([
            'tenant_id'  => 1,
            'user_id'    => $customer->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($customer)->get('/account/wishlist');

        $response->assertStatus(200)
            ->assertSee('My Wishlist')
            ->assertSee($product->name);
    }

    public function test_customer_can_move_wishlist_item_to_cart()
    {
        $customer = User::first() ?: User::create([
            'name'     => 'Jane Customer',
            'email'    => 'jane@sneat.test',
            'password' => bcrypt('secret123'),
        ]);
        $product = Product::with('variants')->first();

        $wishlist = Wishlist::create([
            'tenant_id'  => 1,
            'user_id'    => $customer->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($customer)->postJson("/account/wishlist/{$wishlist->id}/move-to-cart");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('wishlists', [
            'id' => $wishlist->id,
        ]);
    }
}
