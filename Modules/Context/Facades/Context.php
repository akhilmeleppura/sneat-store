<?php

namespace Modules\Context\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string currentPlatform()
 * @method static \Modules\Context\Models\Tenant|null currentTenant()
 * @method static \Modules\Context\Models\Store|null currentStore()
 * @method static \Modules\Context\Models\Branch|null currentBranch()
 * @method static int|null tenantId()
 * @method static int|null storeId()
 * @method static int|null branchId()
 * @method static bool hasTenant()
 * @method static bool hasStore()
 * @method static bool hasBranch()
 * @method static void setTenant(?\Modules\Context\Models\Tenant $tenant)
 * @method static void setStore(?\Modules\Context\Models\Store $store)
 * @method static void setBranch(?\Modules\Context\Models\Branch $branch)
 * @method static void configureTenantDatabase(?\Modules\Context\Models\Tenant $tenant)
 * @method static void clear()
 *
 * @see \Modules\Context\Services\ContextService
 */
class Context extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'context';
    }
}
