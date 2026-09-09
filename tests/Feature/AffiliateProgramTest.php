<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\Models\Affiliate;
use Modules\Order\Models\AffiliateReferral;
use Modules\Order\Models\Order;
use Modules\Order\Services\AffiliateService;
use Tests\TestCase;

class AffiliateProgramTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_user_affiliate_profile_creation_and_link()
    {
        $user = User::factory()->create(['name' => 'John Partner', 'email' => 'john.affiliate@example.com']);
        $service = app(AffiliateService::class);

        $affiliate = $service->getOrCreateAffiliateForUser($user, 12.50);

        $this->assertNotNull($affiliate->affiliate_code);
        $this->assertEquals(12.50, $affiliate->commission_rate);
        $this->assertEquals(0.00, $affiliate->total_earnings);
        $this->assertStringContainsString('?ref=' . $affiliate->affiliate_code, $affiliate->referral_url);
    }

    public function test_vanity_redirect_sets_attribution_cookie()
    {
        $user = User::factory()->create();
        $service = app(AffiliateService::class);
        $affiliate = $service->getOrCreateAffiliateForUser($user);

        $response = $this->get('/ref/' . $affiliate->affiliate_code);

        $response->assertRedirect('/');
        $response->assertCookie('sneat_referral_code', $affiliate->affiliate_code);
    }

    public function test_order_referral_commission_calculation_and_recording()
    {
        $affiliateUser = User::factory()->create(['email' => 'partner@influencer.com']);
        $customerUser = User::factory()->create(['email' => 'buyer@regular.com']);

        $service = app(AffiliateService::class);
        $affiliate = $service->getOrCreateAffiliateForUser($affiliateUser, 10.00);

        $order = Order::create([
            'tenant_id'      => 1,
            'store_id'       => 1,
            'user_id'        => $customerUser->id,
            'order_number'   => 'ORD-AFF-TEST-001',
            'customer_name'  => 'Regular Buyer',
            'customer_email' => $customerUser->email,
            'subtotal'       => 250.00,
            'grand_total'    => 250.00,
            'status'         => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
        ]);

        $referral = $service->recordReferralConversion($order, $affiliate->affiliate_code);

        $this->assertNotNull($referral);
        $this->assertEquals(25.00, $referral->commission_amount); // 10% of $250
        $this->assertEquals('pending', $referral->status);

        $affiliate->refresh();
        $this->assertEquals(25.00, $affiliate->total_earnings);
        $this->assertEquals(25.00, $affiliate->pending_earnings);
    }

    public function test_prevent_self_referral()
    {
        $user = User::factory()->create();
        $service = app(AffiliateService::class);
        $affiliate = $service->getOrCreateAffiliateForUser($user);

        $order = Order::create([
            'tenant_id'      => 1,
            'store_id'       => 1,
            'user_id'        => $user->id, // Same user
            'order_number'   => 'ORD-SELF-REF-002',
            'customer_name'  => $user->name,
            'customer_email' => $user->email,
            'subtotal'       => 200.00,
            'grand_total'    => 200.00,
            'status'         => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
        ]);

        $referral = $service->recordReferralConversion($order, $affiliate->affiliate_code);

        $this->assertNull($referral);
        $this->assertEquals(0.00, $affiliate->fresh()->total_earnings);
    }

    public function test_customer_referral_portal_page()
    {
        $user = User::first() ?: User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.referrals.index'));

        $response->assertStatus(200);
        $response->assertSee('Referral & Affiliate Program');
        $response->assertSee('Earn');
    }

    public function test_admin_affiliates_dashboard_and_payout()
    {
        $admin = User::first() ?: User::factory()->create();
        $partner = User::factory()->create();

        $service = app(AffiliateService::class);
        $affiliate = $service->getOrCreateAffiliateForUser($partner, 10.00);

        $order = Order::create([
            'tenant_id'      => 1,
            'store_id'       => 1,
            'order_number'   => 'ORD-PAYOUT-TEST',
            'customer_name'  => 'Client',
            'customer_email' => 'client@test.com',
            'subtotal'       => 100.00,
            'grand_total'    => 100.00,
            'status'         => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cod',
        ]);

        $referral = $service->recordReferralConversion($order, $affiliate->affiliate_code);

        // Admin index
        $response = $this->actingAs($admin)->get(route('admin.affiliates.index'));
        $response->assertStatus(200);
        $response->assertSee('Affiliate Partners');

        // Admin mark paid
        $payResponse = $this->actingAs($admin)->post(route('admin.affiliates.mark_paid', $referral->id));
        $payResponse->assertRedirect();

        $referral->refresh();
        $this->assertEquals('paid', $referral->status);
        $affiliate->refresh();
        $this->assertEquals(0.00, $affiliate->pending_earnings);
        $this->assertEquals(10.00, $affiliate->paid_earnings);
    }
}
