<?php

namespace Modules\Billing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Taxes\Tax;
use Modules\General\App\Models\Company;
use Modules\General\App\Models\Branch;


// use Modules\Billing\Database\Factories\BillingInvoiceItemFactory;

class BillingInvoiceItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'billing_invoices_items';

    protected $fillable = [
        'document_id',
        'item_id',
        'tax_id',
        'company_id',
        'branch_id',
        'quantity',
        'selling_unit_price',
        'discount_rate',
        'discount_amount',
        'discount_type',
        'subtotal',
    ];

    public $timestamps = true;

    protected $casts = [
        'taxes' => 'array', // JSON column will be cast to array automatically
    ];

    /**
     * Relationship: An item belongs to an invoice
     */

    /**
     * Relationship: Belongs to a company
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relationship: Belongs to a branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    
    public function getTaxesAttribute($value)
    {
        return Tax::whereIn('id', json_decode($value, true))->get();
    }
    
public function invoice()
{
    return $this->belongsTo(BillingInvoice::class, 'document_id', 'id');
}

public function item()
{
    return $this->belongsTo(BillingItem::class, 'item_id', 'id');
}
}
