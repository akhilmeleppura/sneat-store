<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verifications';

    protected $fillable = [
        'identifier',
        'code',
        'type',
        'is_verified',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function isValid(string $inputCode): bool
    {
        return ! $this->is_verified
            && $this->expires_at->isFuture()
            && hash_equals((string) $this->code, (string) $inputCode);
    }
}
