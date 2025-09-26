<?php

namespace Modules\Billing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Taxes\Tax;

class BillingCreditNoteItem extends Model
{
    use HasFactory;

    protected $table = 'billing_credit_note_items';

    protected $fillable = [
        'document_id',
        'item_id',
        'description',
        'quantity',
        'selling_unit_price',
        'tax_id',
        'discount_rate',
        'subtotal',
        'company_id',
        'branch_id'
    ];

    public function document()
    {
        return $this->belongsTo(BillingCreditNote::class, 'document_id');
    }

    public function item()
    {
        return $this->belongsTo(BillingItem::class, 'item_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
