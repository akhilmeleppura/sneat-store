<?php

namespace Tests\Feature;

use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Order\Models\Order;
use Tests\TestCase;

class HeadlessApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_api_store_info_returns_valid_payload()
    {
        $response = $this->getJson('/api/v1/store/info');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'store_name',
                    'currencies',
                    'current_currency',
                    'payment_options',
                    'api_version',
                ],
            ]);
    }

    public function test_api_products_list_with_pagination()
    {
        $response = $this->getJson('/api/v1/store/products?per_page=5');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_api_product_detail_by_slug()
    {
        $product = Product::first();

        $response = $this->getJson("/api/v1/store/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonPath('data.product.id', $product->id);
    }

    public function test_api_categories_list()
    {
        $response = $this->getJson('/api/v1/store/categories');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'products_count'],
                ],
            ]);
    }

    public function test_api_track_order()
    {
        $order = Order::first();

        $response = $this->postJson('/api/v1/store/orders/track', [
            'order_number' => $order->order_number,
            'email'        => $order->customer_email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'order_number' => $order->order_number,
                    'status'       => $order->status,
                ],
            ]);
    }
}
