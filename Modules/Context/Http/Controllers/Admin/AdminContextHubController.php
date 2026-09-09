<?php

namespace Modules\Context\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Currency;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;

class AdminContextHubController extends Controller
{
    /**
     * Display the unified Multi-Tenancy & Store Management Hub.
     */
    public function index(Request $request): View
    {
        $tenants = Tenant::with(['stores.branches', 'defaultStore'])->orderBy('name')->get();
        $stores = Store::withoutTenancy()->with(['tenant', 'branches', 'defaultBranch'])->orderBy('name')->get();
        $branches = Branch::withoutTenancy()->with(['tenant', 'store'])->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();

        // Statistics
        $stats = [
            'total_tenants'    => $tenants->count(),
            'active_tenants'   => $tenants->where('status', 'active')->count(),
            'total_stores'     => $stores->count(),
            'active_stores'    => $stores->where('status', 'active')->count(),
            'total_branches'   => $branches->count(),
            'active_branches'  => $branches->where('status', 'active')->count(),
            'active_currencies'=> $currencies->count(),
        ];

        $currentTenant = Context::currentTenant();
        $currentStore = Context::currentStore();
        $currentBranch = Context::currentBranch();

        return view('context::admin.hub.index', compact(
            'tenants',
            'stores',
            'branches',
            'currencies',
            'stats',
            'currentTenant',
            'currentStore',
            'currentBranch'
        ));
    }

    /**
     * Store a new Tenant (provisions default Store and Branch).
     */
    public function storeTenant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'slug'       => 'nullable|string|max:100|unique:tenants,slug',
            'domain'     => 'nullable|string|max:255|unique:tenants,domain',
            'status'     => 'required|in:active,inactive,suspended',
            'timezone'   => 'nullable|string|max:50',
            'currency'   => 'nullable|string|max:10',
            'locale'     => 'nullable|string|max:10',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        // Ensure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        DB::transaction(function () use ($validated, $slug) {
            $tenant = Tenant::create([
                'name'     => $validated['name'],
                'slug'     => $slug,
                'domain'   => $validated['domain'] ?? null,
                'status'   => $validated['status'],
                'timezone' => $validated['timezone'] ?? 'UTC',
                'currency' => $validated['currency'] ?? 'USD',
                'locale'   => $validated['locale'] ?? 'en',
            ]);

            // Auto-provision default Store
            $store = Store::create([
                'tenant_id'  => $tenant->id,
                'name'       => $tenant->name . ' Store',
                'slug'       => 'main',
                'domain'     => $tenant->domain,
                'is_default' => true,
                'status'     => 'active',
            ]);

            // Auto-provision default Branch
            Branch::create([
                'tenant_id'   => $tenant->id,
                'store_id'    => $store->id,
                'name'        => 'Headquarters',
                'slug'        => 'hq',
                'code'        => strtoupper(substr($slug, 0, 3)) . '-HQ',
                'is_default'  => true,
                'status'      => 'active',
            ]);
        });

        return redirect()->route('admin.context.index', ['tab' => 'tenants'])
            ->with('success', 'Tenant and default store/branch hierarchy created successfully.');
    }

    /**
     * Update an existing Tenant.
     */
    public function updateTenant(Request $request, int $id): RedirectResponse
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'slug'     => 'required|string|max:100|unique:tenants,slug,' . $tenant->id,
            'domain'   => 'nullable|string|max:255|unique:tenants,domain,' . $tenant->id,
            'status'   => 'required|in:active,inactive,suspended',
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'locale'   => 'nullable|string|max:10',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $tenant->update($validated);

        return redirect()->route('admin.context.index', ['tab' => 'tenants'])
            ->with('success', 'Tenant updated successfully.');
    }

    /**
     * Delete a Tenant.
     */
    public function deleteTenant(int $id): RedirectResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->delete();

        return redirect()->route('admin.context.index', ['tab' => 'tenants'])
            ->with('success', 'Tenant deleted successfully.');
    }

    /**
     * Store a new Store under a Tenant.
     */
    public function storeStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id'  => 'required|exists:tenants,id',
            'name'       => 'required|string|max:255',
            'slug'       => 'nullable|string|max:100',
            'domain'     => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
            'status'     => 'required|in:active,inactive',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $isDefault = !empty($validated['is_default']);

        DB::transaction(function () use ($validated, $slug, $isDefault) {
            if ($isDefault) {
                Store::withoutTenancy()->where('tenant_id', $validated['tenant_id'])->update(['is_default' => false]);
            }

            $store = Store::create([
                'tenant_id'  => $validated['tenant_id'],
                'name'       => $validated['name'],
                'slug'       => $slug,
                'domain'     => $validated['domain'] ?? null,
                'is_default' => $isDefault,
                'status'     => $validated['status'],
            ]);

            // Auto-provision initial Branch for new Store
            Branch::create([
                'tenant_id'   => $validated['tenant_id'],
                'store_id'    => $store->id,
                'name'        => $store->name . ' Branch 1',
                'slug'        => 'branch-1',
                'code'        => strtoupper(substr($slug, 0, 3)) . '-01',
                'is_default'  => true,
                'status'      => 'active',
            ]);
        });

        return redirect()->route('admin.context.index', ['tab' => 'stores'])
            ->with('success', 'Store created with initial branch successfully.');
    }

    /**
     * Update an existing Store.
     */
    public function updateStore(Request $request, int $id): RedirectResponse
    {
        $store = Store::withoutTenancy()->findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'slug'       => 'required|string|max:100',
            'domain'     => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
            'status'     => 'required|in:active,inactive',
        ]);

        $isDefault = !empty($validated['is_default']);

        DB::transaction(function () use ($store, $validated, $isDefault) {
            if ($isDefault) {
                Store::withoutTenancy()->where('tenant_id', $store->tenant_id)
                    ->where('id', '!=', $store->id)
                    ->update(['is_default' => false]);
            }

            $store->update([
                'name'       => $validated['name'],
                'slug'       => Str::slug($validated['slug']),
                'domain'     => $validated['domain'] ?? null,
                'is_default' => $isDefault,
                'status'     => $validated['status'],
            ]);
        });

        return redirect()->route('admin.context.index', ['tab' => 'stores'])
            ->with('success', 'Store updated successfully.');
    }

    /**
     * Delete a Store.
     */
    public function deleteStore(int $id): RedirectResponse
    {
        $store = Store::withoutTenancy()->findOrFail($id);
        $store->delete();

        return redirect()->route('admin.context.index', ['tab' => 'stores'])
            ->with('success', 'Store deleted successfully.');
    }

    /**
     * Store a new Branch under a Store.
     */
    public function storeBranch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id'    => 'required|exists:stores,id',
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:100',
            'code'        => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'country'     => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:30',
            'phone'       => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:150',
            'is_default'  => 'nullable|boolean',
            'status'      => 'required|in:active,inactive',
        ]);

        $store = Store::withoutTenancy()->findOrFail($validated['store_id']);
        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $code = !empty($validated['code']) ? strtoupper($validated['code']) : strtoupper(substr($slug, 0, 4) . '-' . rand(10, 99));
        $isDefault = !empty($validated['is_default']);

        DB::transaction(function () use ($store, $validated, $slug, $code, $isDefault) {
            if ($isDefault) {
                Branch::withoutTenancy()->where('store_id', $store->id)->update(['is_default' => false]);
            }

            Branch::create([
                'tenant_id'   => $store->tenant_id,
                'store_id'    => $store->id,
                'name'        => $validated['name'],
                'slug'        => $slug,
                'code'        => $code,
                'address'     => $validated['address'] ?? null,
                'city'        => $validated['city'] ?? null,
                'state'       => $validated['state'] ?? null,
                'country'     => $validated['country'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'phone'       => $validated['phone'] ?? null,
                'email'       => $validated['email'] ?? null,
                'is_default'  => $isDefault,
                'status'      => $validated['status'],
            ]);
        });

        return redirect()->route('admin.context.index', ['tab' => 'branches'])
            ->with('success', 'Branch created successfully.');
    }

    /**
     * Update an existing Branch.
     */
    public function updateBranch(Request $request, int $id): RedirectResponse
    {
        $branch = Branch::withoutTenancy()->findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:100',
            'code'        => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'country'     => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:30',
            'phone'       => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:150',
            'is_default'  => 'nullable|boolean',
            'status'      => 'required|in:active,inactive',
        ]);

        $isDefault = !empty($validated['is_default']);

        DB::transaction(function () use ($branch, $validated, $isDefault) {
            if ($isDefault) {
                Branch::withoutTenancy()->where('store_id', $branch->store_id)
                    ->where('id', '!=', $branch->id)
                    ->update(['is_default' => false]);
            }

            $branch->update([
                'name'        => $validated['name'],
                'slug'        => Str::slug($validated['slug']),
                'code'        => !empty($validated['code']) ? strtoupper($validated['code']) : $branch->code,
                'address'     => $validated['address'] ?? null,
                'city'        => $validated['city'] ?? null,
                'state'       => $validated['state'] ?? null,
                'country'     => $validated['country'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'phone'       => $validated['phone'] ?? null,
                'email'       => $validated['email'] ?? null,
                'is_default'  => $isDefault,
                'status'      => $validated['status'],
            ]);
        });

        return redirect()->route('admin.context.index', ['tab' => 'branches'])
            ->with('success', 'Branch updated successfully.');
    }

    /**
     * Delete a Branch.
     */
    public function deleteBranch(int $id): RedirectResponse
    {
        $branch = Branch::withoutTenancy()->findOrFail($id);
        $branch->delete();

        return redirect()->route('admin.context.index', ['tab' => 'branches'])
            ->with('success', 'Branch deleted successfully.');
    }

    /**
     * Quick switch active session Tenant, Store, and Branch.
     */
    public function switchContext(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'store_id'  => 'nullable|exists:stores,id',
            'branch_id' => 'nullable|exists:tenant_branches,id',
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        session(['current_tenant_id' => $tenant->id]);
        Context::setTenant($tenant);

        if (!empty($validated['store_id'])) {
            $store = Store::withoutTenancy()->where('tenant_id', $tenant->id)->find($validated['store_id']);
            if ($store) {
                session(['current_store_id' => $store->id]);
                Context::setStore($store);
            }
        } else {
            $defaultStore = $tenant->defaultStore()->first() ?? $tenant->stores()->first();
            if ($defaultStore) {
                session(['current_store_id' => $defaultStore->id]);
                Context::setStore($defaultStore);
            }
        }

        if (!empty($validated['branch_id'])) {
            $branch = Branch::withoutTenancy()->find($validated['branch_id']);
            if ($branch) {
                session(['current_branch_id' => $branch->id, 'active_branch_id' => $branch->id]);
                Context::setBranch($branch);
            }
        } else {
            $store = Context::currentStore();
            if ($store) {
                $defaultBranch = $store->defaultBranch()->first() ?? $store->branches()->first();
                if ($defaultBranch) {
                    session(['current_branch_id' => $defaultBranch->id, 'active_branch_id' => $defaultBranch->id]);
                    Context::setBranch($defaultBranch);
                }
            }
        }

        return redirect()->back()
            ->with('success', 'Active context successfully switched to ' . $tenant->name . '.');
    }
}
