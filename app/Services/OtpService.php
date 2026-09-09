<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Session;

class OtpService
{
    /**
     * Generate and dispatch a 6-digit OTP code.
     */
    public function sendOtp(string $identifier, string $type = 'checkout'): array
    {
        $identifier = trim($identifier);

        // Invalidate older pending OTPs for this identifier
        OtpVerification::where('identifier', $identifier)
            ->where('type', $type)
            ->where('is_verified', false)
            ->delete();

        $code = (string) random_int(100000, 999999);

        $otp = OtpVerification::create([
            'identifier'  => $identifier,
            'code'        => $code,
            'type'        => $type,
            'is_verified' => false,
            'expires_at'  => now()->addMinutes(10),
            'ip_address'  => request()?->ip(),
        ]);

        return [
            'status'     => 'success',
            'message'    => "Verification code has been sent to {$identifier}.",
            'demo_code'  => $code, // Displayed in UI modal for hassle-free testing
            'expires_in' => 600,
            'expires_at' => $otp->expires_at->toIso8601String(),
        ];
    }

    /**
     * Verify an input OTP code against the active record.
     */
    public function verifyOtp(string $identifier, string $code, string $type = 'checkout'): array
    {
        $identifier = trim($identifier);
        $code       = trim($code);

        $otp = OtpVerification::where('identifier', $identifier)
            ->where('type', $type)
            ->where('is_verified', false)
            ->latest()
            ->first();

        if (!$otp) {
            return [
                'status'  => 'error',
                'message' => 'No active OTP request found. Please request a new code.',
            ];
        }

        if ($otp->expires_at->isPast()) {
            return [
                'status'  => 'error',
                'message' => 'The verification code has expired. Please request a new code.',
            ];
        }

        if (!hash_equals((string) $otp->code, (string) $code)) {
            return [
                'status'  => 'error',
                'message' => 'Invalid verification code. Please check and try again.',
            ];
        }

        $otp->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        Session::put("otp_verified_{$type}_{$identifier}", true);

        return [
            'status'  => 'success',
            'message' => 'Phone / Email verified successfully!',
        ];
    }

    /**
     * Check if the identifier has verified its OTP in the current session.
     */
    public function isVerified(string $identifier, string $type = 'checkout'): bool
    {
        $identifier = trim($identifier);

        if (Session::get("otp_verified_{$type}_{$identifier}")) {
            return true;
        }

        return OtpVerification::where('identifier', $identifier)
            ->where('type', $type)
            ->where('is_verified', true)
            ->where('verified_at', '>=', now()->subHours(1))
            ->exists();
    }
}
