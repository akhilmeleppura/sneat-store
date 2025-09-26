<?php

namespace App\Models\Customers;

use Modules\Billing\App\Models\BillingInvoice;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'address', 'customer_type_id'];

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function invoices()
    {
        return $this->hasMany(BillingInvoice::class);
    }
}
