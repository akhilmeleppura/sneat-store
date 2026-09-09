<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_can_send_otp_successfully()
    {
        $response = $this->postJson('/otp/send', [
            'identifier' => '+15551234567',
            'type'       => 'checkout',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure(['status', 'message', 'demo_code', 'expires_at']);

        $this->assertDatabaseHas('otp_verifications', [
            'identifier'  => '+15551234567',
            'type'        => 'checkout',
            'is_verified' => false,
        ]);
    }

    public function test_can_verify_otp_with_correct_code()
    {
        $sendResponse = $this->postJson('/otp/send', [
            'identifier' => '+15559876543',
            'type'       => 'checkout',
        ]);

        $code = $sendResponse->json('demo_code');
        $this->assertNotEmpty($code);

        $verifyResponse = $this->postJson('/otp/verify', [
            'identifier' => '+15559876543',
            'code'       => $code,
            'type'       => 'checkout',
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('otp_verifications', [
            'identifier'  => '+15559876543',
            'is_verified' => true,
        ]);
    }

    public function test_fails_verification_with_invalid_code()
    {
        $this->postJson('/otp/send', [
            'identifier' => '+15550001111',
            'type'       => 'checkout',
        ]);

        $verifyResponse = $this->postJson('/otp/verify', [
            'identifier' => '+15550001111',
            'code'       => '000000',
            'type'       => 'checkout',
        ]);

        $verifyResponse->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);
    }
}
