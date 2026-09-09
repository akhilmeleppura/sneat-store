<?php

namespace Tests\Feature;

use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\ProductCompareService;
use Modules\Catalog\Services\RecommendationService;
use Modules\Catalog\Services\SearchService;
use Tests\TestCase;

class SearchAndRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_search_page_renders_with_filters()
    {
        $response = $this->get('/store/search?q=test');

        $response->assertStatus(200);
        $response->assertSee('Results for');

        $emptySearch = $this->get('/store/search');
        $emptySearch->assertStatus(200);
        $emptySearch->assertSee('Browse and Discover Catalog');
    }

    public function test_autocomplete_returns_json_results()
    {
        $product = Product::first();
        $query = substr($product->name, 0, 4);

        $response = $this->getJson('/store/search/autocomplete?q=' . urlencode($query));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'query',
                    'products',
                    'categories',
                    'total',
                ],
            ]);
    }

    public function test_product_compare_lifecycle()
    {
        $product1 = Product::first();
        $product2 = Product::skip(1)->first();

        // 1. Add first product to compare
        $res1 = $this->postJson('/store/compare/add', ['product_id' => $product1->id]);
        $res1->assertStatus(200)->assertJson(['status' => 'success', 'count' => 1]);

        // 2. Add second product to compare
        $res2 = $this->postJson('/store/compare/add', ['product_id' => $product2->id]);
        $res2->assertStatus(200)->assertJson(['status' => 'success', 'count' => 2]);

        // 3. View comparison page
        $comparePage = $this->get('/store/compare');
        $comparePage->assertStatus(200);
        $comparePage->assertSee($product1->name);
        $comparePage->assertSee($product2->name);

        // 4. Remove one product
        $res3 = $this->postJson('/store/compare/remove', ['product_id' => $product1->id]);
        $res3->assertStatus(200)->assertJson(['status' => 'success', 'count' => 1]);
    }

    public function test_recommendations_tracking()
    {
        $product = Product::first();
        $service = app(RecommendationService::class);

        $service->trackRecentlyViewed($product->id);
        $recent = $service->getRecentlyViewed();

        $this->assertTrue($recent->contains('id', $product->id));
    }
}
