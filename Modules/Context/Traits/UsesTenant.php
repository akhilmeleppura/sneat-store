<?php

namespace Modules\Context\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Context\Facades\Context;
use Modules\Context\Scopes\TenantScope;

trait UsesTenant
{
    /**
     * Boot the tenant scoping trait for a model.
     */
    public static function bootUsesTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (empty($model->tenant_id) && Context::hasTenant()) {
                $model->tenant_id = Context::tenantId();
            }
        });
    }

    /**
     * Scope a query to ignore tenant restrictions.
     */
    public function scopeWithoutTenancy(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Alias for withoutTenancy.
     */
    public function scopeAllTenants(Builder $query): Builder
    {
        return $this->scopeWithoutTenancy($query);
    }

    /**
     * Scope a query to a specific tenant ID explicitly.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId);
    }
}
