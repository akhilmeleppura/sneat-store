<?php

namespace Modules\Context\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Context\Facades\Context;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply scope if a tenant is currently resolved and tenancy is not bypassed
        if (Context::hasTenant()) {
            $builder->where($model->qualifyColumn('tenant_id'), Context::tenantId());
        }
    }
}
