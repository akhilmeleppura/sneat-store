<?php

namespace Modules\Payment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\UsesTenant;

class CustomerPaymentMethod extends Model
{
    use UsesTenant;

    protected $table = 'customer_payment_methods';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'gateway',
        'payment_method_token',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
