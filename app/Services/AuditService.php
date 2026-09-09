<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Context\Facades\Context;

class AuditService
{
    /**
     * Log an e-commerce audit event.
     */
    public static function log(
        string $action,
        ?Model $entity = null,
        ?string $description = null,
        array $payload = []
    ): ?AuditLog {
        try {
            $tenantId = Context::tenantId();
            $userId   = Auth::id();

            return AuditLog::create([
                'tenant_id'   => $tenantId,
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => $entity ? get_class($entity) : null,
                'entity_id'   => $entity ? $entity->getKey() : null,
                'description' => $description ?: "Action '{$action}' executed.",
                'payload'     => $payload,
                'ip_address'  => request()?->ip(),
                'user_agent'  => request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Ensure auditing never breaks primary transaction
            \Illuminate\Support\Facades\Log::warning("Audit logging failed: " . $e->getMessage());
            return null;
        }
    }
}
