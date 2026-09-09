<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Currency;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Models\InventoryStock;
use Modules\Marketplace\Models\Vendor;
use Modules\Order\Models\ShippingMethod;
use Modules\Payment\Models\CustomerPaymentMethod;
use Modules\Payment\Models\PaymentTransaction;
use Tests\TestCase;

class VaultedCheckoutAndVendorIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected User $customer;
    protected Product $product;
    protected ProductVariant $variant;
    protected ShippingMethod $shippingMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Integration Tenant',
            'slug' => 'integration-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Integration Store',
            'slug'       => 'integration-store-' . Str::random(5),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Main Hub',
            'slug'       => 'main-hub-' . Str::random(5),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name'          => 'US Dollar',
                'symbol'        => '$',
                'exchange_rate' => 1.0000,
                'is_default'    => true,
                'status'        => 'active',
            ]
        );

        $this->customer = User::create([
            'name'      => 'Alice Buyer',
            'email'     => 'alice.buyer.' . Str::random(5) . '@example.com',
            'password'  => bcrypt('password123'),
            'tenant_id' => $this->tenant->id,
        ]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Electronics',
            'slug'      => 'electronics-' . Str::random(4),
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $category->id,
            'name'        => 'Wireless ANC Headphones',
            'slug'        => 'wireless-anc-headphones-' . Str::random(4),
            'sku'         => 'WANC-01-' . Str::random(4),
            'price'       => 199.99,
            'status'      => 'published',
        ]);

        $this->variant = ProductVariant::create([
            'tenant_id'  => $this->tenant->id,
            'product_id' => $this->product->id,
            'name'       => 'Matte Black',
            'sku'        => 'WANC-MB-' . Str::random(4),
            'price'      => 199.99,
            'is_active'  => true,
        ]);

        InventoryStock::create([
            'tenant_id'          => $this->tenant->id,
            'tenant_branch_id'   => $this->branch->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity_on_hand'   => 50,
            'quantity_reserved'  => 0,
        ]);

        $this->shippingMethod = ShippingMethod::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Express Courier Delivery',
            'code'      => 'fedex_express',
            'carrier'   => 'FedEx',
            'base_rate' => 15.00,
            'min_days'  => 1,
            'max_days'  => 2,
            'is_active' => true,
        ]);
    }

    /**
     * Helper to populate cart for the test user.
     */
    protected function setupCart(): Cart
    {
        $this->actingAs($this->customer);
        $cartService = app(\Modules\Cart\Services\CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1);

        return $cart;
    }

    /**
     * Test checkout page renders vaulted card selection for authenticated customers.
     */
    public function test_checkout_view_displays_saved_payment_methods_for_authenticated_customer(): void
    {
        $this->setupCart();

        CustomerPaymentMethod::create([
            'tenant_id'            => $this->tenant->id,
            'user_id'              => $this->customer->id,
            'gateway'              => 'stripe',
            'payment_method_token' => 'pm_tok_test_4242',
            'card_brand'           => 'visa',
            'card_last_four'       => '4242',
            'card_exp_month'       => '12',
            'card_exp_year'        => '2028',
            'is_default'           => true,
        ]);

        $response = $this->actingAs($this->customer)->get(route('store.checkout.index'));
        $response->assertStatus(200);
        $response->assertSee('Saved Card (1-Click Vault Checkout)');
        $response->assertSee('4242');
        $response->assertSee('visa');
    }

    /**
     * Test authenticated customer can complete 1-click checkout with a vaulted card.
     */
    public function test_authenticated_customer_can_checkout_with_vaulted_card(): void
    {
        $this->setupCart();

        $savedCard = CustomerPaymentMethod::create([
            'tenant_id'            => $this->tenant->id,
            'user_id'              => $this->customer->id,
            'gateway'              => 'stripe',
            'payment_method_token' => 'pm_tok_alice_vault',
            'card_brand'           => 'mastercard',
            'card_last_four'       => '8888',
            'card_exp_month'       => '07',
            'card_exp_year'        => '2029',
            'is_default'           => true,
        ]);

        $payload = [
            'customer_name'           => 'Alice Buyer',
            'customer_email'          => $this->customer->email,
            'customer_phone'          => '+1 (555) 321-7654',
            'shipping_method_id'      => $this->shippingMethod->id,
            'shipping_address'        => [
                'street'      => '123 Market St',
                'city'        => 'San Francisco',
                'state'       => 'CA',
                'country'     => 'US',
                'postal_code' => '94105',
            ],
            'payment_method'          => 'saved_card',
            'saved_payment_method_id' => $savedCard->id,
        ];

        $response = $this->actingAs($this->customer)
            ->post(route('store.checkout.place'), $payload);

        $response->assertRedirect();

        // Verify order in database
        $this->assertDatabaseHas('orders', [
            'user_id'        => $this->customer->id,
            'payment_method' => 'saved_card',
            'payment_status' => 'paid',
        ]);

        // Verify PaymentTransaction created
        $this->assertDatabaseHas('payment_transactions', [
            'gateway' => 'stripe',
            'status'  => 'successful',
        ]);

        $transaction = PaymentTransaction::where('gateway', 'stripe')->latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('8888', $transaction->payment_method_details['last_four']);
        $this->assertEquals('mastercard', $transaction->payment_method_details['brand']);
    }

    /**
     * Test checkout rejects unauthorized saved payment method IDs.
     */
    public function test_unauthorized_saved_payment_method_id_is_rejected(): void
    {
        $this->setupCart();

        $otherUser = User::create([
            'name'      => 'Bob Thief',
            'email'     => 'bob.thief.' . Str::random(4) . '@example.com',
            'password'  => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
        ]);

        $stolenCard = CustomerPaymentMethod::create([
            'tenant_id'            => $this->tenant->id,
            'user_id'              => $otherUser->id,
            'gateway'              => 'stripe',
            'payment_method_token' => 'pm_tok_stolen',
            'card_brand'           => 'visa',
            'card_last_four'       => '9999',
            'card_exp_month'       => '01',
            'card_exp_year'        => '2030',
            'is_default'           => true,
        ]);

        $payload = [
            'customer_name'           => 'Alice Buyer',
            'customer_email'          => $this->customer->email,
            'customer_phone'          => '+1 (555) 321-7654',
            'shipping_method_id'      => $this->shippingMethod->id,
            'shipping_address'        => [
                'street'      => '123 Market St',
                'city'        => 'San Francisco',
                'state'       => 'CA',
                'country'     => 'US',
                'postal_code' => '94105',
            ],
            'payment_method'          => 'saved_card',
            'saved_payment_method_id' => $stolenCard->id,
        ];

        // Should return 404 because customer does not own this card
        $response = $this->actingAs($this->customer)
            ->post(route('store.checkout.place'), $payload);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('orders', ['user_id' => $this->customer->id]);
    }

    /**
     * Test product detail page and catalog display vendor touchpoints and links.
     */
    public function test_product_detail_page_displays_vendor_storefront_link(): void
    {
        $vendor = Vendor::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $this->customer->id,
            'name'            => 'Nova Audio Systems',
            'slug'            => 'nova-audio-systems',
            'email'           => 'nova@example.com',
            'commission_rate' => 15.00,
            'balance'         => 0.00,
            'status'          => 'active',
        ]);

        $this->product->update(['vendor_id' => $vendor->id]);

        $response = $this->get(route('storefront.product.show', $this->product->slug));
        $response->assertStatus(200);
        $response->assertSee('Nova Audio Systems');
        $response->assertSee(route('storefront.vendor.show', $vendor->slug));
        $response->assertSee('Verified Marketplace Merchant');

        // Catalog also includes vendor attribution
        $catalogResponse = $this->get(route('storefront.catalog'));
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('Nova Audio Systems');
    }

    /**
     * Test front navbar renders vendor onboarding and customer payment vault links.
     */
    public function test_front_navbar_renders_vendor_onboarding_and_payment_vault_links(): void
    {
        // Guest user visits home
        $guestResponse = $this->get(route('storefront.home'));
        $guestResponse->assertStatus(200);
        $guestResponse->assertSee(route('storefront.vendor.register'));
        $guestResponse->assertSee('Sell on Sneat');

        // Authenticated customer visits home
        $authResponse = $this->actingAs($this->customer)->get(route('storefront.home'));
        $authResponse->assertStatus(200);
        $authResponse->assertSee(route('account.payment_methods.index'));
        $authResponse->assertSee('Payment Methods');
    }
}
