<?php

namespace Modules\Context\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Resolve Tenant Context
        $tenant = Context::resolveTenantFromRequest($request);

        // 2. Resolve Store Context if Tenant exists
        if ($tenant) {
            $storeKey = $request->header(config('context.headers.store', 'X-Store-Key'))
                ?? $request->query('store')
                ?? ($request->hasSession() ? session('current_store_id') : null);

            if ($storeKey) {
                $store = is_numeric($storeKey)
                    ? Store::where('tenant_id', $tenant->id)->find((int) $storeKey)
                    : Store::where('tenant_id', $tenant->id)->where('slug', $storeKey)->first();

                if ($store) {
                    Context::setStore($store);
                }
            }

            // 3. Resolve Branch Context if Store exists
            $store = Context::currentStore();
            if ($store) {
                $branchKey = $request->header(config('context.headers.branch', 'X-Branch-Key'))
                    ?? $request->query('branch')
                    ?? ($request->hasSession() ? (session('current_branch_id') ?? session('active_branch_id')) : null);

                if ($branchKey) {
                    $branch = is_numeric($branchKey)
                        ? Branch::where('store_id', $store->id)->find((int) $branchKey)
                        : Branch::where('store_id', $store->id)->where('slug', $branchKey)->first();

                    if ($branch) {
                        Context::setBranch($branch);
                    }
                }
            }

            // 4. Share Context with Blade Views for Sneat Admin layout
            View::share('currentTenant', Context::currentTenant());
            View::share('currentStore', Context::currentStore());
            View::share('currentBranch', Context::currentBranch());
        }

        return $next($request);
    }
}
