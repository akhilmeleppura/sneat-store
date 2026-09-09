<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\Models\Order;
use Modules\Order\Models\TaxRate;
use Modules\Order\Services\TaxEngineService;
use Modules\Payment\Services\PaymentVaultService;
use Tests\TestCase;

class TaxAndPaymentVaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_tax_engine_regional_and_b2b_exemption()
    {
        $taxService = app(TaxEngineService::class);

        // 1. Create a regional tax rate for Canada (ON = 13%)
        TaxRate::create([
            'country_code'    => 'CA',
            'state_code'      => 'ON',
            'tax_name'        => 'HST Ontario',
            'rate_percentage' => 13.00,
            'is_b2b_exempt'   => true,
            'is_active'       => true,
        ]);

        // Calculate consumer tax
        $calcConsumer = $taxService->calculateTax(100.00, 'CA', 'ON', false);
        $this->assertEquals(13.00, $calcConsumer['tax_amount']);
        $this->assertEquals(13.00, $calcConsumer['tax_rate']);
        $this->assertFalse($calcConsumer['is_exempt']);

        // Calculate B2B wholesale tax
        $calcB2B = $taxService->calculateTax(100.00, 'CA', 'ON', true);
        $this->assertEquals(0.00, $calcB2B['tax_amount']);
        $this->assertTrue($calcB2B['is_exempt']);
    }

    public function test_payment_vault_token_storage()
    {
        $user = User::first() ?: User::factory()->create();
        $vaultService = app(PaymentVaultService::class);

        $saved = $vaultService->saveToken(
            $user->id,
            'stripe',
            'pm_tok_test_123456789',
            'Visa',
            '4242',
            '12',
            '2028',
            true
        );

        $this->assertNotNull($saved->id);
        $this->assertEquals('visa', $saved->card_brand);
        $this->assertEquals('4242', $saved->card_last_four);
        $this->assertTrue($saved->is_default);

        $methods = $vaultService->getUserMethods($user->id);
        $this->assertCount(1, $methods);
    }
}
