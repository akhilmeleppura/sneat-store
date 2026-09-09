<?php

namespace Modules\Order\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Modules\Order\Services\AffiliateService;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function __construct(protected AffiliateService $affiliateService)
    {
    }

    /**
     * Handle an incoming request for affiliate tracking.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $refCode = $request->query('ref') ?? $request->header('X-Affiliate-Code');

        $response = $next($request);

        if ($refCode) {
            $affiliate = $this->affiliateService->getAffiliateByCode($refCode);
            if ($affiliate) {
                // 30-day attribution cookie (30 * 24 * 60 minutes)
                $cookie = cookie()->make('sneat_referral_code', $affiliate->affiliate_code, 43200);
                $response->withCookie($cookie);
            }
        }

        return $response;
    }
}
