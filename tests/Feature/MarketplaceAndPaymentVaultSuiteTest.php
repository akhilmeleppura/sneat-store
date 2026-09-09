<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Marketplace\Models\Vendor;
use Modules\Payment\Models\CustomerPaymentMethod;
use Tests\TestCase;

class MarketplaceAndPaymentVaultSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Marketplace Vault Tenant',
            'slug' => 'market-vault-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Marketplace Store',
            'slug'       => 'market-store-' . Str::random(5),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Main Fulfillment Branch',
            'slug'       => 'main-branch-' . Str::random(5),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->customerUser = User::create([
            'name'      => 'Jane Customer',
            'email'     => 'jane.customer.' . Str::random(6) . '@example.com',
            'password'  => bcrypt('secret123'),
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test public vendor storefront renders, handles JSON, and filters products.
     */
    public function test_public_vendor_storefront_renders_and_filters_products(): void
    {
        $vendor = Vendor::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $this->customerUser->id,
            'name'            => 'Apex Hardware Pro',
            'slug'            => 'apex-hardware-pro',
            'email'           => 'apex@example.com',
            'phone'           => '+1 (555) 123-4567',
            'description'     => 'Quality professional hardware and tools.',
            'commission_rate' => 12.50,
            'balance'         => 0.00,
            'status'          => 'active',
        ]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Power Tools',
            'slug'      => 'power-tools-' . Str::random(4),
            'is_active' => true,
        ]);

        $product1 = Product::create([
            'tenant_id'   => $this->tenant->id,
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'name'        => 'Cordless Drill 20V',
            'slug'        => 'cordless-drill-20v-' . Str::random(4),
            'sku'         => 'DRL-20V-' . Str::random(4),
            'price'       => 129.99,
            'status'      => 'published',
        ]);

        $product2 = Product::create([
            'tenant_id'   => $this->tenant->id,
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'name'        => 'Rotary Hammer 1500W',
            'slug'        => 'rotary-hammer-1500w-' . Str::random(4),
            'sku'         => 'HMR-1500W-' . Str::random(4),
            'price'       => 249.99,
            'status'      => 'published',
        ]);

        // HTML storefront visit
        $response = $this->get(route('storefront.vendor.show', $vendor->slug));
        $response->assertStatus(200);
        $response->assertSee('Apex Hardware Pro');
        $response->assertSee('Cordless Drill 20V');
        $response->assertSee('Rotary Hammer 1500W');

        // JSON response visit
        $jsonResponse = $this->getJson(route('storefront.vendor.show', $vendor->slug));
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'vendor'   => ['id', 'name', 'slug', 'status'],
            'products' => ['data', 'total', 'current_page'],
        ]);
        $this->assertEquals(2, $jsonResponse->json('products.total'));

        // Sorting by price desc
        $sortResponse = $this->get(route('storefront.vendor.show', [$vendor->slug, 'sort' => 'price_desc']));
        $sortResponse->assertStatus(200);

        // Inactive vendor returns 404
        $inactiveVendor = Vendor::create([
            'tenant_id' => $this->tenant->id,
            'user_id'   => $this->customerUser->id,
            'name'      => 'Suspended Shop',
            'slug'      => 'suspended-shop',
            'email'     => 'suspended@example.com',
            'status'    => 'inactive',
        ]);

        $inactiveResponse = $this->get(route('storefront.vendor.show', $inactiveVendor->slug));
        $inactiveResponse->assertStatus(404);
    }

    /**
     * Test seller onboarding form and application submission for guests and authenticated users.
     */
    public function test_vendor_onboarding_form_and_registration_submission(): void
    {
        // Public registration form renders
        $formResponse = $this->get(route('storefront.vendor.register'));
        $formResponse->assertStatus(200);
        $formResponse->assertSee('Sell on Sneat Store');

        // Guest submits application
        $guestPayload = [
            'name'           => 'Nordic Crafts & Woodwork',
            'email'          => 'nordic.crafts.' . Str::random(4) . '@example.test',
            'phone'          => '+1 (800) 555-0199',
            'description'    => 'Handcrafted wooden homeware.',
            'bank_name'      => 'Chase Bank',
            'account_number' => '1234567890',
            'routing_number' => '021000021',
        ];

        $submitResponse = $this->post(route('storefront.vendor.register.post'), $guestPayload);
        $submitResponse->assertRedirect(route('storefront.vendor.register'));

        $this->assertDatabaseHas('marketplace_vendors', [
            'name'   => 'Nordic Crafts & Woodwork',
            'status' => 'pending',
        ]);

        $createdVendor = Vendor::where('name', 'Nordic Crafts & Woodwork')->first();
        $this->assertNotNull($createdVendor);
        $this->assertEquals('nordic-crafts-woodwork', $createdVendor->slug);
        $this->assertIsArray($createdVendor->payout_info);
        $this->assertEquals('Chase Bank', $createdVendor->payout_info['bank_name']);

        // Authenticated user submits via JSON
        $user = User::create([
            'name'      => 'Mark Artisan',
            'email'     => 'mark.artisan.' . Str::random(4) . '@example.test',
            'password'  => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
        ]);

        $authPayload = [
            'name'           => 'Artisan Studio',
            'email'          => $user->email,
            'phone'          => '+1 (555) 999-8888',
            'description'    => 'Handmade ceramics and vases.',
            'bank_name'      => 'Wells Fargo',
            'account_number' => '9876543210',
            'routing_number' => '12100024',
        ];

        $jsonSubmit = $this->actingAs($user)->postJson(route('storefront.vendor.register.post'), $authPayload);
        $jsonSubmit->assertStatus(201);
        $jsonSubmit->assertJsonPath('status', 'success');
        $jsonSubmit->assertJsonPath('vendor.status', 'pending');

        // Attempting duplicate registration when already pending
        $dupSubmit = $this->actingAs($user)->postJson(route('storefront.vendor.register.post'), $authPayload);
        $dupSubmit->assertStatus(422);
        $dupSubmit->assertJsonPath('status', 'error');
    }

    /**
     * Test customer payment methods vaulting, listing, and validation.
     */
    public function test_customer_payment_methods_listing_and_creation(): void
    {
        // Unauthenticated access redirects to login
        $guestAccess = $this->get(route('account.payment_methods.index'));
        $guestAccess->assertRedirect('/login');

        // Authenticated access with empty vault
        $response = $this->actingAs($this->customerUser)->get(route('account.payment_methods.index'));
        $response->assertSee('Payment Vault');
        $response->assertSee('Saved Cards');
        $response->assertSee('No Saved Payment Methods');

        // Vault a new card
        $cardPayload = [
            'card_holder_name' => 'Jane Customer',
            'card_number'      => '4242 4242 4242 4242',
            'card_exp_month'   => '08',
            'card_exp_year'    => '2028',
            'card_brand'       => 'visa',
            'gateway'          => 'stripe',
            'is_default'       => 1,
        ];

        $vaultResponse = $this->actingAs($this->customerUser)->post(route('account.payment_methods.store'), $cardPayload);
        $vaultResponse->assertRedirect(route('account.payment_methods.index'));

        $this->assertDatabaseHas('customer_payment_methods', [
            'user_id'        => $this->customerUser->id,
            'card_brand'     => 'visa',
            'card_last_four' => '4242',
            'card_exp_month' => '08',
            'card_exp_year'  => '2028',
            'is_default'     => true,
        ]);

        // Vault a second card via JSON API
        $card2Payload = [
            'card_holder_name' => 'Jane Customer Business',
            'card_number'      => '5555 4444 3333 9876',
            'card_exp_month'   => '11',
            'card_exp_year'    => '2029',
            'gateway'          => 'stripe',
            'is_default'       => 0,
        ];

        $jsonVault = $this->actingAs($this->customerUser)->postJson(route('account.payment_methods.store'), $card2Payload);
        $jsonVault->assertStatus(201);
        $jsonVault->assertJsonPath('status', 'success');
        $jsonVault->assertJsonPath('payment_method.card_last_four', '9876');
        $jsonVault->assertJsonPath('payment_method.card_brand', 'mastercard');

        // View payment methods index in JSON
        $jsonIndex = $this->actingAs($this->customerUser)->getJson(route('account.payment_methods.index'));
        $jsonIndex->assertStatus(200);
        $this->assertCount(2, $jsonIndex->json('payment_methods'));
    }

    /**
     * Test setting default payment method and removing vaulted card.
     */
    public function test_customer_payment_method_set_default_and_deletion(): void
    {
        $card1 = CustomerPaymentMethod::create([
            'tenant_id'            => $this->tenant->id,
            'user_id'              => $this->customerUser->id,
            'gateway'              => 'stripe',
            'payment_method_token' => 'pm_tok_test_1',
            'card_brand'           => 'visa',
            'card_last_four'       => '1111',
            'card_exp_month'       => '05',
            'card_exp_year'        => '2027',
            'is_default'           => true,
        ]);

        $card2 = CustomerPaymentMethod::create([
            'tenant_id'            => $this->tenant->id,
            'user_id'              => $this->customerUser->id,
            'gateway'              => 'stripe',
            'payment_method_token' => 'pm_tok_test_2',
            'card_brand'           => 'mastercard',
            'card_last_four'       => '2222',
            'card_exp_month'       => '09',
            'card_exp_year'        => '2028',
            'is_default'           => false,
        ]);

        // Set card2 as default
        $setDefaultResponse = $this->actingAs($this->customerUser)
            ->postJson(route('account.payment_methods.default', $card2->id));

        $setDefaultResponse->assertStatus(200);
        $setDefaultResponse->assertJsonPath('status', 'success');

        $this->assertFalse($card1->fresh()->is_default);
        $this->assertTrue($card2->fresh()->is_default);

        // Delete default card (card2)
        $deleteResponse = $this->actingAs($this->customerUser)
            ->deleteJson(route('account.payment_methods.destroy', $card2->id));

        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('customer_payment_methods', ['id' => $card2->id]);
        // card1 promoted to default automatically
        $this->assertTrue($card1->fresh()->is_default);

        // Attempting to delete another customer's card fails (404)
        $otherUser = User::create([
            'name'      => 'Other User',
            'email'     => 'other.' . Str::random(4) . '@example.test',
            'password'  => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
        ]);

        $otherCard = CustomerPaymentMethod::create([
            'tenant_id'            => $this->tenant->id,
            'user_id'              => $otherUser->id,
            'gateway'              => 'stripe',
            'payment_method_token' => 'pm_tok_other_user',
            'card_brand'           => 'amex',
            'card_last_four'       => '3333',
            'card_exp_month'       => '01',
            'card_exp_year'        => '2030',
            'is_default'           => true,
        ]);

        $unauthorizedDelete = $this->actingAs($this->customerUser)
            ->deleteJson(route('account.payment_methods.destroy', $otherCard->id));

        $unauthorizedDelete->assertStatus(404);
        $this->assertDatabaseHas('customer_payment_methods', ['id' => $otherCard->id]);
    }
}
