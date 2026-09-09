<?php

namespace Modules\Marketplace\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Context\Facades\Context;
use Modules\Marketplace\Models\Vendor;

class VendorRegistrationController extends Controller
{
    /**
     * Show seller onboarding application form.
     */
    public function showRegistrationForm()
    {
        $user = Auth::user();
        if ($user) {
            $existingVendor = Vendor::where('user_id', $user->id)->first();
            if ($existingVendor) {
                if ($existingVendor->status === 'active') {
                    return redirect()->route('vendor.dashboard')
                        ->with('info', 'You already have an active seller store.');
                }
                return view('marketplace::storefront.register', [
                    'pendingVendor' => $existingVendor,
                ]);
            }
        }

        return view('marketplace::storefront.register');
    }

    /**
     * Process seller registration application.
     */
    public function register(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'phone'          => 'nullable|string|max:30',
            'description'    => 'nullable|string|max:1000',
            'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'routing_number' => 'nullable|string|max:50',
        ]);

        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name'      => $validated['name'],
                    'password'  => bcrypt(Str::random(16)),
                    'tenant_id' => Context::tenantId() ?? 1,
                ]
            );
        }

        $existingVendor = Vendor::where('user_id', $user->id)->first();
        if ($existingVendor) {
            if ($existingVendor->status === 'active') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'You already have an active seller store.',
                    ], 422);
                }
                return redirect()->route('vendor.dashboard')
                    ->with('info', 'You already have an active seller store.');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'A seller application for this account is already under review.',
                ], 422);
            }
            return redirect()->route('storefront.vendor.register')
                ->with('info', 'Your seller application is already pending administrator approval.');
        }

        $slugBase = Str::slug($validated['name']);
        $slug = $slugBase;
        $counter = 1;
        while (Vendor::where('slug', $slug)->exists()) {
            $slug = "{$slugBase}-{$counter}";
            $counter++;
        }

        $payoutInfo = [
            'bank_name'      => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'routing_number' => $validated['routing_number'] ?? null,
        ];

        $vendor = Vendor::create([
            'tenant_id'       => Context::tenantId() ?? 1,
            'user_id'         => $user->id,
            'name'            => $validated['name'],
            'slug'            => $slug,
            'email'           => $validated['email'],
            'phone'           => $validated['phone'] ?? null,
            'description'     => $validated['description'] ?? null,
            'commission_rate' => 15.00,
            'balance'         => 0.00,
            'status'          => 'pending',
            'payout_info'     => $payoutInfo,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Seller application for '{$vendor->name}' submitted successfully.",
                'vendor'  => $vendor,
            ], 201);
        }

        return redirect()->route('storefront.vendor.register')
            ->with('success', "Your seller application for '{$vendor->name}' has been submitted! Our marketplace team will review your details and notify you once approved.");
    }
}
