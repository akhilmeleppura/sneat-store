<?php

namespace Modules\Context\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;

class StoreBranchController extends Controller
{
    /**
     * Switch user's active branch / warehouse location.
     */
    public function switchBranch(int $id)
    {
        $branch = Branch::withoutGlobalScopes()->findOrFail($id);

        Session::put('selected_branch_id', $branch->id);
        Session::put('active_branch_id', $branch->id);
        Context::setBranch($branch);

        if (request()->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Active branch switched to {$branch->name}",
                'branch'  => $branch,
            ]);
        }

        return redirect()->back()->with('success', "Active store branch switched to {$branch->name}.");
    }

    /**
     * Public interactive store locator page with Map.
     */
    public function locations()
    {
        $tenantId = Context::tenantId();

        $branches = Branch::withoutGlobalScopes()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('status', 'active')
            ->get();

        if ($branches->isEmpty()) {
            $branches = Branch::withoutGlobalScopes()->where('status', 'active')->get();
        }

        $activeBranch = Context::branch();

        return view('context::storefront.locations', compact('branches', 'activeBranch'));
    }

    /**
     * JSON feed of branches for interactive map pins.
     */
    public function apiBranches(): JsonResponse
    {
        $tenantId = Context::tenantId();

        $branches = Branch::withoutGlobalScopes()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('status', 'active')
            ->get()
            ->map(function ($b) {
                return [
                    'id'           => $b->id,
                    'name'         => $b->name,
                    'code'         => $b->code,
                    'address'      => $b->full_address,
                    'full_address' => $b->full_address,
                    'phone'        => $b->phone ?? '+1 (800) 555-STORE',
                    'email'        => $b->email ?? 'store@sneat-retail.com',
                    'lat'          => $b->latitude,
                    'lng'          => $b->longitude,
                    'latitude'     => $b->latitude,
                    'longitude'    => $b->longitude,
                    'is_default'   => $b->is_default,
                    'switch_url'   => route('branch.switch', $b->id),
                ];
            });

        return response()->json([
            'status'           => 'success',
            'active_branch_id' => Session::get('active_branch_id') ?: Context::branchId(),
            'branches'         => $branches,
            'data'             => $branches,
        ]);
    }
}
