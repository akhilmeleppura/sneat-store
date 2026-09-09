<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\General\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{
    /**
     * Subscribe email to newsletter (AJAX).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($validated['email']));

        $existing = NewsletterSubscriber::where('email', $email)->first();
        if ($existing) {
            if ($existing->status === 'subscribed') {
                return response()->json([
                    'status'  => 'info',
                    'message' => 'You are already subscribed to our newsletter.',
                ]);
            }
            $existing->update(['status' => 'subscribed', 'verified_at' => now()]);
            return response()->json([
                'status'  => 'success',
                'message' => 'Welcome back! Your subscription has been reactivated.',
            ]);
        }

        $token = Str::random(32);
        NewsletterSubscriber::create([
            'email'       => $email,
            'status'      => 'subscribed',
            'token'       => $token,
            'verified_at' => now(),
            'ip_address'  => $request->ip(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Thank you for subscribing to our newsletter! Check your inbox for exclusive updates.',
        ]);
    }

    /**
     * Verify subscriber email.
     */
    public function verify(string $token)
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();
        $subscriber->update([
            'status'      => 'subscribed',
            'verified_at' => now(),
        ]);

        return redirect()->route('storefront.home')->with('success', 'Your newsletter subscription has been confirmed!');
    }
}
