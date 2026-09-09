<?php

namespace Modules\Marketplace\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Marketplace\Models\Vendor;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVendor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Platform supreme admins can always access vendor preview/portal
        if ($user->is_supreme_admin ?? false) {
            return $next($request);
        }

        $vendor = Vendor::where('user_id', $user->id)->first();

        if (!$vendor) {
            abort(403, 'You do not have a registered Vendor account.');
        }

        if ($vendor->status !== 'active') {
            abort(403, "Your Vendor account is currently {$vendor->status}. Please contact platform administrators.");
        }

        // Share active vendor with current request
        $request->attributes->set('current_vendor', $vendor);

        return $next($request);
    }
}
