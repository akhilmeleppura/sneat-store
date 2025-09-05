<?php

namespace Modules\Billing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Taxes\Tax;
use Modules\Billing\App\Models\BillingCreditNoteItem;
use App\Models\Customers\Customer;
use Modules\General\App\Models\Company;
use Modules\General\App\Models\Branch;

class BillingCreditNote extends Model
{
    use HasFactory;

    protected $table = 'billing_credit_notes';
    
    protected $fillable = [
        'document_prefix',
        'document_number',
        'customer_id',
        'invoice_id',
        'issue_date',
        'due_date',
        'sub_total',
        'document_discount_type',
        'document_discount_rate',
        'document_discount_amount',
        'document_tax_id',
        'note',
        'payment_status',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice()
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    public function items()
    {
        return $this->hasMany(BillingCreditNoteItem::class, 'document_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'document_tax_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo('App\Models\User', 'updated_by');
    }

       public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
        public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}