<?php

namespace Modules\Billing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Customers\Customer;
use Modules\Billing\App\Models\BillingInvoiceItem;
use Modules\Billing\App\Models\BillingDebitNote;
use Modules\Billing\App\Models\BillingCreditNote;
use Modules\General\App\Models\DocumentTemplate;

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
        'document_tax_id',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'due_date'   => 'datetime',
    ];

    public $timestamps = true;

    /* ----------------------
       Relationships
    ---------------------- */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(BillingInvoiceItem::class, 'document_id');
    }

    public function debitNotes()
    {
        return $this->hasMany(BillingDebitNote::class, 'invoice_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(BillingCreditNote::class, 'invoice_id');
    }

    public function status()
    {
        return $this->belongsTo(BillingPaymentStatus::class, 'payment_status', 'value');
    }

    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /* ----------------------
       Computed Attributes
    ---------------------- */

    // Total of all item taxes
    public function getTotalTaxAttribute()
    {
        return $this->items->sum(fn($item) => $item->tax_amount);
    }

    // Total of all item discounts
    public function getTotalItemDiscountAttribute()
    {
        return $this->items->sum(fn($item) => $item->discount_amount);
    }

    // Total after document-level discount
    public function getTotalAfterDiscountAttribute()
    {
        $subtotal = $this->sub_total;
        $discount = $this->document_discount_amount ?? 0;
        return $subtotal - $discount;
    }

    // Grand total (subtotal - doc discount + taxes)
    public function getGrandTotalAttribute()
    {
        return $this->total_after_discount + $this->total_tax;
    }

    /* ----------------------
       Accessors for labels
    ---------------------- */
    public function getPaymentStatusLabelAttribute()
    {
        return match ($this->payment_status) {
            0 => 'Not Paid',
            1 => 'Paid',
            2 => 'Partially Paid',
            default => 'Unknown',
        };
    }

    public function getDiscountTypeLabelAttribute()
    {
        return match ($this->document_discount_type) {
            1 => 'Percentage',
            2 => 'Fixed Amount',
            default => 'None',
        };
    }
}
