<?php

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\UsesTenant;

class RfqQuote extends Model
{
    use UsesTenant;

    protected $table = 'rfq_quotes';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'quote_number',
        'company_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'tax_id',
        'items_payload',
        'quoted_total',
        'status',
        'valid_until',
        'notes',
    ];

    protected $casts = [
        'items_payload' => 'array',
        'quoted_total'  => 'decimal:2',
        'valid_until'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
