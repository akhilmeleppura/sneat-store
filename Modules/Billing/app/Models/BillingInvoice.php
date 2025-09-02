<?php

namespace  Modules\Billing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Customers\Customer;
use Modules\Billing\App\Models\BillingInvoiceItem;

class BillingInvoice extends Model
{
    use HasFactory;

   protected $table = 'billing_invoices';

    protected $fillable = [
        'document_prefix',
        'document_number',
        'customer_id',
        'issue_date',
        'due_date',
        'sub_total',
        'document_discount_type',
        'document_discount_rate',
        'document_discount_amount',
        'payment_status',
        'document_tax_id'
    ];

    protected $casts = [
         'issue_date' => 'datetime',
         'due_date'=>'datetime',
];


    public $timestamps = true;

    /**
     * Relationship: Invoice belongs to a customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Accessor for payment status label
     */
    public function getPaymentStatusLabelAttribute()
    {
        return match ($this->payment_status) {
            0 => 'Not Paid',
            1 => 'Paid',
            2 => 'Partially Paid',
            default => 'Unknown',
        };
    }

    /**
     * Accessor for discount type label
     */
    public function getDiscountTypeLabelAttribute()
    {
        return match ($this->document_discount_type) {
            1 => 'Percentage',
            2 => 'Fixed Amount',
            default => 'None',
        };
    }

    public function status()
{
    return $this->belongsTo(BillingPaymentStatus::class, 'payment_status', 'value');
}
// In app/Models/BillingInvoiceItem.php
 
    public function items()
    {
        return $this->hasMany(BillingInvoiceItem::class, 'document_id');
    }

}
