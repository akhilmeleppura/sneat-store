<?php

namespace Modules\Context\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;

class ContextSwitcherController extends Controller
{
    /**
     * Switch user's active tenant context.
     */
    public function switchTenant(int $id, Request $request): JsonResponse|RedirectResponse
    {
        $tenant = Tenant::active()->findOrFail($id);

        session(['current_tenant_id' => $tenant->id]);
        Context::setTenant($tenant);

        // Auto-select the tenant's default store (or first store)
        $store = $tenant->defaultStore ?? $tenant->stores()->first();
        if ($store) {
            session(['current_store_id' => $store->id]);
            Context::setStore($store);

            $branch = $store->defaultBranch ?? $store->branches()->first();
            if ($branch) {
                session([
                    'current_branch_id' => $branch->id,
                    'active_branch_id'  => $branch->id,
                ]);
                Context::setBranch($branch);
            }
        } else {
            session()->forget(['current_store_id', 'current_branch_id', 'active_branch_id']);
            Context::setStore(null);
            Context::setBranch(null);
        }

        $message = "Active tenant switched to {$tenant->name}.";

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'tenant'  => $tenant,
                'store'   => $store,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Switch user's active store context within current tenant or cross-tenant.
     */
    public function switchStore(int $id, Request $request): JsonResponse|RedirectResponse
    {
        $store = Store::withoutGlobalScopes()->findOrFail($id);
        $tenant = $store->tenant;

        if ($tenant) {
            session(['current_tenant_id' => $tenant->id]);
            Context::setTenant($tenant);
        }

        session(['current_store_id' => $store->id]);
        Context::setStore($store);

        // Auto-select store's default branch
        $branch = $store->defaultBranch ?? $store->branches()->first();
        if ($branch) {
            session([
                'current_branch_id' => $branch->id,
                'active_branch_id'  => $branch->id,
            ]);
            Context::setBranch($branch);
        }

        $message = "Active store switched to {$store->name}.";

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'store'   => $store,
                'tenant'  => $tenant,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Switch user's active store branch / warehouse context.
     */
    public function switchBranch(int $id, Request $request): JsonResponse|RedirectResponse
    {
        $branch = Branch::withoutGlobalScopes()->findOrFail($id);

        session([
            'current_branch_id' => $branch->id,
            'active_branch_id'  => $branch->id,
        ]);
        Context::setBranch($branch);

        if ($branch->store) {
            session(['current_store_id' => $branch->store_id]);
            Context::setStore($branch->store);
        }

        if ($branch->tenant) {
            session(['current_tenant_id' => $branch->tenant_id]);
            Context::setTenant($branch->tenant);
        }

        $message = "Active branch switched to {$branch->name}.";

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'branch'  => $branch,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
