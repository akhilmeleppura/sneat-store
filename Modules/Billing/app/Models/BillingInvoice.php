<?php

namespace Modules\Billing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Customers\Customer;
use Modules\Billing\App\Models\BillingInvoiceItem;
use Modules\Billing\App\Models\BillingDebitNote;
use Modules\Billing\App\Models\BillingCreditNote;

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
        'due_date' => 'datetime',
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
     * Relationship: Invoice has many items
     */
    public function items()
    {
        return $this->hasMany(BillingInvoiceItem::class, 'document_id');
    }
    
    /**
     * Relationship: Invoice has many debit notes
     */
    public function debitNotes()
    {
        return $this->hasMany(BillingDebitNote::class, 'invoice_id');
    }
    
    /**
     * Relationship: Invoice has many credit notes
     */
    public function creditNotes()
    {
        return $this->hasMany(BillingCreditNote::class, 'invoice_id');
    }
    
    /**
     * Relationship: Invoice belongs to a payment status
     */
    public function status()
    {
        return $this->belongsTo(BillingPaymentStatus::class, 'payment_status', 'value');
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
}