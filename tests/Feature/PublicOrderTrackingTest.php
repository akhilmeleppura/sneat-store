<?php

namespace Tests\Feature;

use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\Models\Order;
use Tests\TestCase;

class PublicOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_public_tracking_search_page_renders()
    {
        $response = $this->get('/track-order');

        $response->assertStatus(200)
            ->assertSee('Track Your Order Status');
    }

    public function test_guest_can_track_valid_order()
    {
        $order = Order::first();

        $response = $this->post('/track-order', [
            'order_number' => $order->order_number,
            'email'        => $order->customer_email,
        ]);

        $response->assertStatus(200)
            ->assertSee($order->order_number)
            ->assertSee($order->status)
            ->assertSee('Confirmed');
    }

    public function test_tracking_fails_with_invalid_credentials()
    {
        $response = $this->post('/track-order', [
            'order_number' => 'INVALID-ORDER-999',
            'email'        => 'wrong-email@example.com',
        ]);

        $response->assertStatus(302)
            ->assertSessionHas('error');
    }
}
