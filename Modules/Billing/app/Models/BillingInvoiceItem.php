<?php

namespace Modules\Billing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Taxes\Tax;
use Modules\General\App\Models\Company;
use Modules\General\App\Models\Branch;

class BillingInvoiceItem extends Model
{
    use HasFactory;

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
        'taxes' => 'array',
    ];

    /**
     * Belongs to company
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Belongs to branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Access taxes (stored as JSON IDs)
     */
    public function getTaxesAttribute($value)
    {
        return Tax::whereIn('id', json_decode($value, true))->get();
    }

    /**
     * Belongs to invoice
     */
    public function invoice()
    {
        return $this->belongsTo(BillingInvoice::class, 'document_id', 'id');
    }

    /**
     * Belongs to billing item
     */
    public function billingItem()
    {
        return $this->belongsTo(BillingItem::class, 'item_id', 'id');
    }
}
