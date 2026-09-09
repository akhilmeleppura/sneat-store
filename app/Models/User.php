<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Modules\General\App\Models\Company;
use Modules\General\App\Models\Branch;

class User extends Authenticatable
{
    use HasRoles;
    use HasApiTokens;
    protected $guard_name = 'web';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_super_admin',
        'is_supreme_admin',
        'company_id',
        'branch_id',
        'tenant_id',
        'store_id',
        'tenant_branch_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tenant()
    {
        return $this->belongsTo(\Modules\Context\Models\Tenant::class, 'tenant_id');
    }

    public function store()
    {
        return $this->belongsTo(\Modules\Context\Models\Store::class, 'store_id');
    }

    public function tenantBranch()
    {
        return $this->belongsTo(\Modules\Context\Models\Branch::class, 'tenant_branch_id');
    }

    public function vendor()
    {
        return $this->hasOne(\Modules\Marketplace\Models\Vendor::class, 'user_id');
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) ($this->is_supreme_admin || $this->is_super_admin || $this->hasRole('Supreme Admin') || $this->hasRole('super-admin') || $this->hasRole('platform-admin'));
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
