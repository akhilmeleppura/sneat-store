<?php

namespace App\Helpers;

use Modules\Context\Facades\Context;

class RbacHelper
{
    /**
     * Generate a tenant-scoped role name.
     * E.g. tenant.1.admin or tenant.admin
     */
    public static function tenantRole(string $role, ?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? Context::tenantId();

        if (! $tenantId) {
            return $role;
        }

        $prefix = config('context.rbac.prefix', 'tenant');
        $sep = config('context.rbac.separator', '.');

        return "{$prefix}{$sep}{$tenantId}{$sep}{$role}";
    }

    /**
     * Generate a tenant-scoped permission name.
     */
    public static function tenantPermission(string $permission, ?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? Context::tenantId();

        if (! $tenantId) {
            return $permission;
        }

        $prefix = config('context.rbac.prefix', 'tenant');
        $sep = config('context.rbac.separator', '.');

        return "{$prefix}{$sep}{$tenantId}{$sep}{$permission}";
    }

    /**
     * Extract the raw role/permission name without the tenant prefix.
     */
    public static function stripTenantPrefix(string $scopedName): string
    {
        $prefix = config('context.rbac.prefix', 'tenant');
        $sep = config('context.rbac.separator', '.');

        $pattern = '/^' . preg_quote($prefix, '/') . preg_quote($sep, '/') . '\d+' . preg_quote($sep, '/') . '/';

        return preg_replace($pattern, '', $scopedName);
    }

    /**
     * Extract the tenant ID from a scoped role or permission name.
     */
    public static function extractTenantId(string $scopedName): ?int
    {
        $prefix = config('context.rbac.prefix', 'tenant');
        $sep = config('context.rbac.separator', '.');

        $pattern = '/^' . preg_quote($prefix, '/') . preg_quote($sep, '/') . '(\d+)' . preg_quote($sep, '/') . '/';

        if (preg_match($pattern, $scopedName, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Check if a role name is tenant-scoped.
     */
    public static function isTenantScoped(string $name): bool
    {
        return self::extractTenantId($name) !== null;
    }
}
