<?php

namespace Tests\Feature;

use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Context\Models\Branch;
use Tests\TestCase;

class BranchLocatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_store_locations_page_renders_successfully()
    {
        $response = $this->get('/store/locations');

        $response->assertStatus(200);
        $response->assertSee('Find a Store / Pickup Location');
        $response->assertSee('branchMap');
    }

    public function test_api_branches_returns_json_locations()
    {
        $response = $this->getJson('/api/branches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'active_branch_id',
                'branches' => [
                    '*' => ['id', 'name', 'code', 'latitude', 'longitude', 'full_address']
                ]
            ]);
    }

    public function test_customer_can_switch_active_branch()
    {
        $branch = Branch::first();
        $this->assertNotNull($branch, 'A branch should exist from seeders');

        $response = $this->get('/branch/switch/' . $branch->id);

        $response->assertRedirect();
        $response->assertSessionHas('active_branch_id', $branch->id);
    }
}
