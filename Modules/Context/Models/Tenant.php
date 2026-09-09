<?php

namespace Modules\Context\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'status',
        'db_connection',
        'db_host',
        'db_port',
        'db_database',
        'db_username',
        'db_password',
        'timezone',
        'currency',
        'locale',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope a query to only active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get all stores belonging to this tenant.
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'tenant_id');
    }

    /**
     * Get the default store for this tenant.
     */
    public function defaultStore(): HasOne
    {
        return $this->hasOne(Store::class, 'tenant_id')->where('is_default', true);
    }

    /**
     * Get all branches belonging to this tenant.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'tenant_id');
    }

    /**
     * Get all settings belonging to this tenant.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(TenantSetting::class, 'tenant_id');
    }

    /**
     * Get all users affiliated with this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    /**
     * Get a tenant setting value by key.
     */
    public function getSetting(string $key, mixed $default = null, string $group = 'general'): mixed
    {
        $setting = $this->settings()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if (! $setting) {
            return $default;
        }

        if ($setting->is_encrypted && $setting->value) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Exception $e) {
                return $setting->value;
            }
        }

        return $setting->value;
    }

    /**
     * Set a tenant setting value.
     */
    public function setSetting(string $key, mixed $value, string $group = 'general', bool $encrypted = false): TenantSetting
    {
        $storeValue = $value;
        if ($encrypted && $value !== null) {
            $storeValue = Crypt::encryptString((string) $value);
        }

        return $this->settings()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $storeValue, 'is_encrypted' => $encrypted]
        );
    }

    /**
     * Check if tenant uses an isolated dedicated database.
     */
    public function hasDedicatedDatabase(): bool
    {
        return ! empty($this->db_database) || ! empty($this->db_connection);
    }

    /**
     * Get dynamic database connection configuration for this tenant.
     */
    public function getDatabaseConfig(): array
    {
        $defaultConfig = config('database.connections.mysql');

        return array_merge($defaultConfig, [
            'host'     => $this->db_host ?: $defaultConfig['host'],
            'port'     => $this->db_port ?: $defaultConfig['port'],
            'database' => $this->db_database ?: $defaultConfig['database'],
            'username' => $this->db_username ?: $defaultConfig['username'],
            'password' => $this->db_password ?: $defaultConfig['password'],
        ]);
    }
}
