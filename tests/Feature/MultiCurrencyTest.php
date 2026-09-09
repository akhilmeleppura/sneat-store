<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Cart\Services\CartService;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Currency;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Context\Services\CurrencyService;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Services\CheckoutService;
use Tests\TestCase;

class MultiCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected User $adminUser;
    protected CurrencyService $currencyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Currency Test Tenant',
            'slug' => 'curr-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Currency Store',
            'slug'       => 'curr-store-' . Str::random(5),
            'code'       => 'STR-' . Str::random(3),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Currency Branch',
            'slug'       => 'curr-branch-' . Str::random(5),
            'code'       => 'BR-' . Str::random(3),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->adminUser = User::create([
            'name'              => 'Currency Admin',
            'email'             => 'admin.curr.' . Str::random(5) . '@sneat.test',
            'password'          => bcrypt('password'),
            'is_supreme_admin'  => true,
            'email_verified_at' => now(),
            'tenant_id'         => $this->tenant->id,
            'store_id'          => $this->store->id,
            'tenant_branch_id'  => $this->branch->id,
        ]);

        $this->currencyService = app(CurrencyService::class);
        $this->currencyService->seedDefaults($this->tenant->id);
    }

    /**
     * Test currencies are properly seeded and accessible through CurrencyService.
     */
    public function test_currency_seeding_and_service_defaults(): void
    {
        $activeCurrencies = $this->currencyService->getActiveCurrencies();
        $this->assertNotEmpty($activeCurrencies);

        $codes = $activeCurrencies->pluck('code')->toArray();
        $this->assertContains('USD', $codes);
        $this->assertContains('EUR', $codes);
        $this->assertContains('GBP', $codes);
        $this->assertContains('JPY', $codes);

        $default = $this->currencyService->getDefaultCurrency();
        $this->assertEquals('USD', $default->code);
        $this->assertEquals('$', $default->symbol);
        $this->assertEquals(1.000000, $default->exchange_rate);
    }

    /**
     * Test precision FX conversion math and formatting rules.
     */
    public function test_fx_conversion_math_and_formatting(): void
    {
        // 100 USD to EUR (rate 0.92) -> 92.00
        $eurAmount = $this->currencyService->convert(100.00, 'USD', 'EUR');
        $this->assertEquals(92.00, $eurAmount);

        // 100 USD to JPY (rate 155.00, 0 decimals) -> 15500
        $jpyAmount = $this->currencyService->convert(100.00, 'USD', 'JPY');
        $this->assertEquals(15500, $jpyAmount);

        // Reverse conversion: 92 EUR to USD -> 100.00
        $usdAmount = $this->currencyService->convert(92.00, 'EUR', 'USD');
        $this->assertEquals(100.00, $usdAmount);

        // Format helpers
        $formattedUsd = $this->currencyService->format(100.50, 'USD');
        $this->assertEquals('$100.50', $formattedUsd);

        $formattedJpy = $this->currencyService->format(15500, 'JPY');
        $this->assertEquals('¥15,500', $formattedJpy);

        // Global money() helper converting from base USD
        $this->currencyService->setCurrentCurrency('EUR');
        $this->assertEquals('€92.00', money(100.00));
    }

    /**
     * Test currency switcher endpoint persists currency in session.
     */
    public function test_currency_switcher_session_persistence(): void
    {
        $response = $this->post(route('currency.switch'), [
            'code' => 'EUR',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('currency', 'EUR');

        // GET endpoint
        $getResponse = $this->get(route('currency.switch.get', 'GBP'));
        $getResponse->assertRedirect();
        $getResponse->assertSessionHas('currency', 'GBP');

        // Active API endpoint
        $apiResponse = $this->getJson(route('currency.api.active'));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonStructure([
            'current' => ['code', 'name', 'symbol', 'exchange_rate'],
            'currencies',
        ]);
    }

    /**
     * Test catalog displays prices converted to active session currency.
     */
    public function test_catalog_displays_converted_currency_prices(): void
    {
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Audio Gear',
            'slug'      => 'audio-gear-' . Str::random(5),
            'is_active' => true,
        ]);

        $product = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $category->id,
            'name'        => 'Wireless Headphones Studio',
            'slug'        => 'wireless-headphones-' . Str::random(5),
            'price'       => 200.00, // 200 USD base
            'status'      => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'AUDIO-STUDIO-' . Str::random(4),
            'price'      => 200.00,
        ]);

        // Default session (USD): should display $200.00
        $responseUsd = $this->get(route('storefront.catalog'));
        $responseUsd->assertStatus(200);
        $responseUsd->assertSee('$200.00');

        // Switch session to EUR (200 * 0.92 = 184.00)
        $responseEur = $this->withSession(['currency' => 'EUR'])
            ->get(route('storefront.catalog'));
        $responseEur->assertStatus(200);
        $responseEur->assertSee('€184.00');

        // Product detail page in EUR
        $detailResponse = $this->withSession(['currency' => 'EUR'])
            ->get(route('storefront.product.show', $product->slug));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('€184.00');
    }

    /**
     * Test checkout creates order with checkout currency, exchange rate, and base grand total.
     */
    public function test_checkout_records_currency_and_exchange_rates(): void
    {
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Camera Tech',
            'slug'      => 'camera-tech-' . Str::random(5),
            'is_active' => true,
        ]);

        $product = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $category->id,
            'name'        => 'Mirrorless Pro Camera',
            'slug'        => 'mirrorless-pro-' . Str::random(5),
            'price'       => 1000.00, // 1000 USD base
            'status'      => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'CAM-PRO-' . Str::random(4),
            'price'      => 1000.00,
        ]);

        \Modules\Inventory\Models\InventoryStock::create([
            'tenant_id'          => $this->tenant->id,
            'tenant_branch_id'   => $this->branch->id,
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'quantity_on_hand'   => 15,
            'quantity_reserved'  => 0,
            'reorder_level'      => 3,
        ]);

        // Add item to cart via CartService
        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($variant->id, 1);

        // Set customer active currency to EUR
        $this->currencyService->setCurrentCurrency('EUR');

        // Process checkout
        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->processCheckout(
            $cart,
            [
                'name'             => 'European Traveler',
                'email'            => 'euro@traveler.com',
                'phone'            => '+49 123 456789',
                'shipping_address' => [
                    'address' => '123 Berlin Str',
                    'city'    => 'Berlin',
                    'country' => 'Germany',
                ],
            ],
            'stripe'
        );

        $this->assertNotNull($order);
        $this->assertEquals('EUR', $order->currency);
        $this->assertEquals(0.920000, $order->exchange_rate);
        $this->assertEquals('USD', $order->base_currency);

        // Base 1000 subtotal + 10% tax (100) = 1100.00 base USD
        $this->assertEquals(1100.00, (float) $order->base_grand_total);
        // Converted EUR: 1100 * 0.92 = 1012.00 EUR
        $this->assertEquals(1012.00, (float) $order->grand_total);

        // Confirmation page renders in EUR
        $confirmationResponse = $this->get(route('store.order.confirmation', $order->order_number));
        $confirmationResponse->assertStatus(200);
        $confirmationResponse->assertSee('€1,012.00');
    }

    /**
     * Test admin can view, update FX rates, toggle active, and set default base currency.
     */
    public function test_admin_currency_management_workflow(): void
    {
        // 1. Admin views currencies panel
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.currencies.index'));

        $response->assertStatus(200);
        $response->assertSee('Currencies', false);
        $response->assertSee('USD');
        $response->assertSee('EUR');
        $response->assertSee('JPY');

        // 2. Admin updates exchange rate for EUR (from 0.92 to 0.95)
        $eurCurrency = Currency::where('code', 'EUR')->first();
        $updateResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.currencies.rate', $eurCurrency->id), [
                'exchange_rate' => 0.950000,
            ]);

        $updateResponse->assertRedirect();
        $this->assertEquals(0.950000, $eurCurrency->fresh()->exchange_rate);

        // 3. Admin toggles status (disable JPY)
        $jpyCurrency = Currency::where('code', 'JPY')->first();
        $this->assertTrue($jpyCurrency->is_active);

        $toggleResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.currencies.toggle', $jpyCurrency->id));

        $toggleResponse->assertRedirect();
        $this->assertFalse($jpyCurrency->fresh()->is_active);

        // 4. Admin creates new custom currency (e.g. CHF)
        $storeResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.currencies.store'), [
                'code'            => 'CHF',
                'name'            => 'Swiss Franc',
                'symbol'          => 'CHF',
                'exchange_rate'   => 0.900000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
            ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('currencies', [
            'code' => 'CHF',
            'name' => 'Swiss Franc',
        ]);
    }
}
