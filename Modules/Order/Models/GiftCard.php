<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Context\Traits\BelongsToTenant;

class GiftCard extends Model
{
    use BelongsToTenant;

    protected $table = 'gift_cards';

    protected $fillable = [
        'tenant_id',
        'code',
        'initial_balance',
        'current_balance',
        'currency',
        'recipient_email',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active'       => 'boolean',
        'expires_at'      => 'datetime',
    ];

    /**
     * Check if card is valid and has balance.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return $this->current_balance > 0;
    }
}
