<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductReview;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Services\ReviewService;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Models\InventoryStock;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected User $adminUser;
    protected User $customerUser;
    protected Product $product;
    protected ProductVariant $variant;
    protected ReviewService $reviewService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Review Test Tenant',
            'slug' => 'rev-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Review Store',
            'slug'       => 'rev-store-' . Str::random(5),
            'code'       => 'STR-' . Str::random(3),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Review Branch',
            'slug'       => 'rev-branch-' . Str::random(5),
            'code'       => 'BR-' . Str::random(3),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->adminUser = User::create([
            'name'              => 'Review Admin',
            'email'             => 'admin.rev.' . Str::random(5) . '@sneat.test',
            'password'          => bcrypt('password'),
            'is_supreme_admin'  => true,
            'email_verified_at' => now(),
            'tenant_id'         => $this->tenant->id,
            'store_id'          => $this->store->id,
            'tenant_branch_id'  => $this->branch->id,
        ]);

        $this->customerUser = User::create([
            'name'              => 'Jane Reviewer',
            'email'             => 'reviewer.' . Str::random(5) . '@sneat.test',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
            'tenant_id'         => $this->tenant->id,
            'store_id'          => $this->store->id,
        ]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Acoustic Audio',
            'slug'      => 'acoustic-audio-' . Str::random(5),
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $category->id,
            'name'        => 'Studio Pro Headphones',
            'slug'        => 'studio-pro-headphones-' . Str::random(5),
            'price'       => 249.00,
            'status'      => 'published',
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'ST-PRO-' . Str::random(4),
            'price'      => 249.00,
        ]);

        InventoryStock::create([
            'tenant_id'          => $this->tenant->id,
            'tenant_branch_id'   => $this->branch->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity_on_hand'   => 25,
            'quantity_reserved'  => 0,
            'reorder_level'      => 3,
        ]);

        $this->reviewService = app(ReviewService::class);
    }

    /**
     * Test customer can submit a review via storefront endpoint.
     */
    public function test_customer_can_submit_review_for_product(): void
    {
        $response = $this->actingAs($this->customerUser)
            ->post(route('storefront.reviews.store', $this->product->slug), [
                'rating'  => 5,
                'title'   => 'Stunning Audio Clarity',
                'comment' => 'The frequency response and bass clarity on these studio headphones are phenomenal.',
            ]);

        $response->assertRedirect(route('storefront.product.show', $this->product->slug));
        $response->assertSessionHas('success');

        $review = ProductReview::where('product_id', $this->product->id)->first();
        $this->assertNotNull($review);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Stunning Audio Clarity', $review->title);
        $this->assertTrue($review->is_approved);

        // Assert product rating aggregate updated
        $this->product->refresh();
        $this->assertEquals(5.00, $this->product->rating_cache);
        $this->assertEquals(1, $this->product->rating_count);
    }

    /**
     * Test verified buyer badge is automatically determined when user has purchased the product.
     */
    public function test_verified_buyer_badge_automatically_awarded(): void
    {
        // 1. Create a historical completed order for this customer and product
        $order = Order::create([
            'tenant_id'          => $this->tenant->id,
            'store_id'           => $this->store->id,
            'tenant_branch_id'   => $this->branch->id,
            'user_id'            => $this->customerUser->id,
            'order_number'       => 'ORD-REV-' . Str::random(5),
            'customer_name'      => $this->customerUser->name,
            'customer_email'     => $this->customerUser->email,
            'subtotal'           => 249.00,
            'grand_total'        => 249.00,
            'currency'           => 'USD',
            'status'             => 'completed',
            'payment_status'     => 'paid',
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'product_name'       => $this->product->name,
            'variant_sku'        => $this->variant->sku,
            'unit_price'         => 249.00,
            'quantity'           => 1,
            'line_total'         => 249.00,
        ]);

        // 2. Submit review
        $review = $this->reviewService->submitReview(
            $this->product->id,
            $this->customerUser->id,
            5,
            'Verified Purchase Review',
            'I bought this last week and the sound quality is top-notch.'
        );

        $this->assertTrue($review->is_verified_buyer);
        $this->assertEquals($order->id, $review->order_id);
    }

    /**
     * Test unverified buyer does not get the verified badge if no completed order exists.
     */
    public function test_unverified_buyer_does_not_get_verified_badge(): void
    {
        $review = $this->reviewService->submitReview(
            $this->product->id,
            $this->customerUser->id,
            4,
            'Prospective Buyer Review',
            'Tested at an audio expo, great overall feel.'
        );

        $this->assertFalse($review->is_verified_buyer);
        $this->assertNull($review->order_id);
    }

    /**
     * Test product rating aggregates update accurately across multiple reviews.
     */
    public function test_product_rating_aggregates_calculated_correctly(): void
    {
        $user2 = User::create([
            'name'      => 'User Two',
            'email'     => 'u2.' . Str::random(4) . '@test.com',
            'password'  => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
        ]);

        $user3 = User::create([
            'name'      => 'User Three',
            'email'     => 'u3.' . Str::random(4) . '@test.com',
            'password'  => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
        ]);

        // Review 1: 5 stars
        $this->reviewService->submitReview($this->product->id, $this->customerUser->id, 5, 'R1', 'Great product');
        // Review 2: 4 stars
        $this->reviewService->submitReview($this->product->id, $user2->id, 4, 'R2', 'Good product');
        // Review 3: 3 stars
        $this->reviewService->submitReview($this->product->id, $user3->id, 3, 'R3', 'Average product');

        $this->product->refresh();

        // Average = (5 + 4 + 3) / 3 = 4.00
        $this->assertEquals(4.00, $this->product->rating_cache);
        $this->assertEquals(3, $this->product->rating_count);
    }

    /**
     * Test rating breakdown distribution math.
     */
    public function test_rating_breakdown_distribution(): void
    {
        // 2 x 5-star reviews, 1 x 4-star review
        $uA = User::create(['name' => 'A', 'email' => 'a.' . Str::random(4) . '@t.com', 'password' => 'x', 'tenant_id' => $this->tenant->id]);
        $uB = User::create(['name' => 'B', 'email' => 'b.' . Str::random(4) . '@t.com', 'password' => 'x', 'tenant_id' => $this->tenant->id]);

        $this->reviewService->submitReview($this->product->id, $this->customerUser->id, 5, null, 'Five star A');
        $this->reviewService->submitReview($this->product->id, $uA->id, 5, null, 'Five star B');
        $this->reviewService->submitReview($this->product->id, $uB->id, 4, null, 'Four star');

        $breakdown = $this->reviewService->getRatingBreakdown($this->product->id);

        $this->assertEquals(4.7, $breakdown['average']);
        $this->assertEquals(3, $breakdown['total_count']);
        $this->assertEquals(2, $breakdown['breakdown'][5]['count']);
        $this->assertEquals(67, $breakdown['breakdown'][5]['percentage']); // 2/3 = 67%
        $this->assertEquals(1, $breakdown['breakdown'][4]['count']);
        $this->assertEquals(33, $breakdown['breakdown'][4]['percentage']); // 1/3 = 33%
        $this->assertEquals(0, $breakdown['breakdown'][3]['count']);
    }

    /**
     * Test admin can view reviews, toggle visibility, and reply.
     */
    public function test_admin_can_toggle_approval_and_reply_to_review(): void
    {
        $review = $this->reviewService->submitReview(
            $this->product->id,
            $this->customerUser->id,
            5,
            'Excellent Quality',
            'Loved the build quality!'
        );

        // 1. Admin Index View
        $indexResponse = $this->actingAs($this->adminUser)->get(route('catalog.reviews.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Loved the build quality!');

        // 2. Toggle Approval to hide
        $toggleResponse = $this->actingAs($this->adminUser)->post(route('catalog.reviews.toggle', $review->id));
        $toggleResponse->assertRedirect();
        $review->refresh();
        $this->assertFalse($review->is_approved);

        // When review is hidden, product rating cache ignores it
        $this->product->refresh();
        $this->assertEquals(0.00, $this->product->rating_cache);
        $this->assertEquals(0, $this->product->rating_count);

        // 3. Post Merchant Reply
        $replyResponse = $this->actingAs($this->adminUser)->post(route('catalog.reviews.reply', $review->id), [
            'admin_reply' => 'Thank you for your valuable feedback!',
        ]);
        $replyResponse->assertRedirect();
        $review->refresh();
        $this->assertEquals('Thank you for your valuable feedback!', $review->admin_reply);
        $this->assertNotNull($review->replied_at);
    }

    /**
     * Test storefront product detail page renders review content and star breakdown.
     */
    public function test_product_detail_page_displays_reviews_and_ratings(): void
    {
        $this->reviewService->submitReview(
            $this->product->id,
            $this->customerUser->id,
            5,
            'Flagship Sound Quality',
            'Best headphones in this price tier by far.'
        );

        $response = $this->get(route('storefront.product.show', $this->product->slug));
        $response->assertStatus(200);
        $response->assertSee('Flagship Sound Quality');
        $response->assertSee('Best headphones in this price tier by far.');
        $response->assertSee('Customer Reviews &amp; Ratings', false);
    }
}
