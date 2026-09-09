<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\Models\Order;
use Tests\TestCase;

class OrderCustomerActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_customer_can_view_printable_invoice()
    {
        $order = Order::first();
        $customer = User::find($order->user_id) ?: User::create([
            'name'     => 'Jane Customer',
            'email'    => 'jane@sneat.test',
            'password' => bcrypt('secret123'),
        ]);
        $order->update(['user_id' => $customer->id]);

        $response = $this->actingAs($customer)->get("/account/orders/{$order->order_number}/invoice");

        $response->assertStatus(200)
            ->assertSee('INVOICE')
            ->assertSee($order->order_number)
            ->assertSee('Billed To:');
    }

    public function test_customer_can_cancel_pending_order()
    {
        $order = Order::first();
        $customer = User::find($order->user_id) ?: User::create([
            'name'     => 'Jane Customer',
            'email'    => 'jane@sneat.test',
            'password' => bcrypt('secret123'),
        ]);
        $order->update([
            'user_id' => $customer->id,
            'status'  => 'pending',
        ]);

        $response = $this->actingAs($customer)->post("/account/orders/{$order->order_number}/cancel", [
            'reason' => 'Ordered the wrong model by mistake.',
        ]);

        $response->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'cancelled',
        ]);

        // Verify audit trail logged
        $this->assertDatabaseHas('ecommerce_audit_logs', [
            'action'    => 'order.cancelled',
            'entity_id' => $order->id,
        ]);
    }

    public function test_customer_can_request_return_for_delivered_order()
    {
        $order = Order::first();
        $customer = User::find($order->user_id) ?: User::create([
            'name'     => 'Jane Customer',
            'email'    => 'jane@sneat.test',
            'password' => bcrypt('secret123'),
        ]);
        $order->update([
            'user_id' => $customer->id,
            'status'  => 'completed',
        ]);

        $response = $this->actingAs($customer)->post("/account/orders/{$order->order_number}/return", [
            'reason' => 'Item color did not match the photo.',
        ]);

        $response->assertStatus(302)
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertTrue($order->metadata['return_requested'] ?? false);
        $this->assertEquals('Item color did not match the photo.', $order->metadata['return_reason'] ?? '');

        // Verify audit log
        $this->assertDatabaseHas('ecommerce_audit_logs', [
            'action'    => 'order.return_requested',
            'entity_id' => $order->id,
        ]);
    }
}
