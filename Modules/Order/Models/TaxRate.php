<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Context\Traits\UsesTenant;

class TaxRate extends Model
{
    use UsesTenant;

    protected $table = 'tax_rates';

    protected $fillable = [
        'tenant_id',
        'country_code',
        'state_code',
        'tax_name',
        'rate_percentage',
        'is_compound',
        'is_b2b_exempt',
        'is_active',
    ];

    protected $casts = [
        'rate_percentage' => 'decimal:2',
        'is_compound'     => 'boolean',
        'is_b2b_exempt'   => 'boolean',
        'is_active'       => 'boolean',
    ];
}
