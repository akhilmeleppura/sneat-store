<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Context\Traits\BelongsToTenant;

class NewsletterSubscriber extends Model
{
    use BelongsToTenant;

    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'tenant_id',
        'email',
        'status',
        'token',
        'verified_at',
        'ip_address',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];
}
