<?php

namespace Modules\Context\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;

class ContextService
{
    protected ?Tenant $currentTenant = null;
    protected ?Store $currentStore = null;
    protected ?Branch $currentBranch = null;
    protected bool $bypassTenancy = false;

    /**
     * Get the current platform identifier.
     */
    public function currentPlatform(): string
    {
        return config('app.name', 'Laravel');
    }

    /**
     * Get the active tenant.
     */
    public function currentTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * Get the active store.
     */
    public function currentStore(): ?Store
    {
        if (! $this->currentStore && $this->currentTenant) {
            $this->currentStore = $this->currentTenant->defaultStore ?? $this->currentTenant->stores()->first();
        }

        return $this->currentStore;
    }

    /**
     * Get the active branch.
     */
    public function currentBranch(): ?Branch
    {
        if (! $this->currentBranch && $this->currentStore()) {
            $this->currentBranch = $this->currentStore()->defaultBranch ?? $this->currentStore()->branches()->first();
        }

        return $this->currentBranch;
    }

    /**
     * Alias for currentBranch()
     */
    public function branch(): ?Branch
    {
        return $this->currentBranch();
    }

    /**
     * Alias for currentStore()
     */
    public function store(): ?Store
    {
        return $this->currentStore();
    }

    /**
     * Alias for currentTenant()
     */
    public function tenant(): ?Tenant
    {
        return $this->currentTenant();
    }

    /**
     * Get current tenant ID.
     */
    public function tenantId(): ?int
    {
        return $this->currentTenant?->id;
    }

    /**
     * Get current store ID.
     */
    public function storeId(): ?int
    {
        return $this->currentStore()?->id;
    }

    /**
     * Get current branch ID.
     */
    public function branchId(): ?int
    {
        return $this->currentBranch()?->id;
    }

    /**
     * Check if a tenant is currently resolved.
     */
    public function hasTenant(): bool
    {
        return ! $this->bypassTenancy && $this->currentTenant !== null;
    }

    /**
     * Check if a store is currently active.
     */
    public function hasStore(): bool
    {
        return $this->currentStore() !== null;
    }

    /**
     * Check if a branch is currently active.
     */
    public function hasBranch(): bool
    {
        return $this->currentBranch() !== null;
    }

    /**
     * Set the current tenant and optionally configure dynamic DB isolation.
     */
    public function setTenant(?Tenant $tenant): self
    {
        $this->currentTenant = $tenant;

        if ($tenant) {
            $this->configureTenantDatabase($tenant);
        }

        return $this;
    }

    /**
     * Set the current store.
     */
    public function setStore(?Store $store): self
    {
        $this->currentStore = $store;
        return $this;
    }

    /**
     * Set the current branch.
     */
    public function setBranch(?Branch $branch): self
    {
        $this->currentBranch = $branch;
        return $this;
    }

    /**
     * Temporarily execute callback while bypassing tenancy isolation.
     */
    public function bypassTenancy(callable $callback): mixed
    {
        $previous = $this->bypassTenancy;
        $this->bypassTenancy = true;

        try {
            return $callback();
        } finally {
            $this->bypassTenancy = $previous;
        }
    }

    /**
     * Resolve tenant from incoming HTTP request.
     */
    public function resolveTenantFromRequest(Request $request): ?Tenant
    {
        $order = config('context.detection_order', ['subdomain', 'header', 'query', 'session', 'auth']);

        foreach ($order as $mode) {
            $tenant = match ($mode) {
                'subdomain' => $this->detectBySubdomain($request),
                'header'    => $this->detectByHeader($request),
                'query'     => $this->detectByQuery($request),
                'session'   => $this->detectBySession($request),
                'auth'      => $this->detectByAuth(),
                default     => null,
            };

            if ($tenant) {
                $this->setTenant($tenant);
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Dynamically configure database connection for dedicated tenant database.
     */
    public function configureTenantDatabase(?Tenant $tenant): void
    {
        if (! $tenant || ! $tenant->hasDedicatedDatabase()) {
            return;
        }

        $connectionName = 'tenant_' . $tenant->id;
        $config = $tenant->getDatabaseConfig();

        Config::set("database.connections.{$connectionName}", $config);
        Config::set('database.default', $connectionName);

        DB::purge($connectionName);
        DB::reconnect($connectionName);
    }

    /**
     * Detect tenant via subdomain.
     */
    protected function detectBySubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $central = config('context.central_domain', 'localhost');

        if ($host === $central || Str::endsWith($host, '127.0.0.1')) {
            return null;
        }

        $parts = explode('.', $host);
        if (count($parts) > 1) {
            $subdomain = $parts[0];
            if ($subdomain !== 'www' && $subdomain !== 'admin') {
                return Tenant::active()->where('slug', $subdomain)->first();
            }
        }

        return Tenant::active()->where('domain', $host)->first();
    }

    /**
     * Detect tenant via HTTP request header.
     */
    protected function detectByHeader(Request $request): ?Tenant
    {
        $headerKey = config('context.headers.tenant', 'X-Tenant-Key');
        $key = $request->header($headerKey);

        if (! $key) {
            return null;
        }

        return is_numeric($key)
            ? Tenant::active()->find((int) $key)
            : Tenant::active()->where('slug', $key)->first();
    }

    /**
     * Detect tenant via query string.
     */
    protected function detectByQuery(Request $request): ?Tenant
    {
        $key = $request->query('tenant');

        if (! $key) {
            return null;
        }

        return is_numeric($key)
            ? Tenant::active()->find((int) $key)
            : Tenant::active()->where('slug', $key)->first();
    }

    /**
     * Detect tenant via session state.
     */
    protected function detectBySession(Request $request): ?Tenant
    {
        if ($request->hasSession()) {
            $tenantId = Session::get('current_tenant_id');
            if ($tenantId) {
                return Tenant::active()->find($tenantId);
            }
        }

        return null;
    }

    /**
     * Detect tenant via authenticated user.
     */
    protected function detectByAuth(): ?Tenant
    {
        $user = Auth::user();

        if ($user && ! empty($user->tenant_id)) {
            return Tenant::active()->find($user->tenant_id);
        }

        return null;
    }

    /**
     * Clear all resolved context state.
     */
    public function clear(): void
    {
        $this->currentTenant = null;
        $this->currentStore = null;
        $this->currentBranch = null;
        $this->bypassTenancy = false;
    }
}
