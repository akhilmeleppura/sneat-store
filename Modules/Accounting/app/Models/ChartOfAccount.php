<?php

namespace Modules\Accounting\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\AccountChartFactory;

class ChartOfAccount extends Model
{
    use HasFactory;

     use HasFactory;

     protected $table = 'accounting_chartofaccounts';

    protected $fillable = [
        'account_name',
        'opening_balance',
        'subcategory_id',
        'main_category_id'
    ];

//     public function subcategory()
// {
//     return $this->belongsTo(SubCategory::class, 'subcategory_id', 'id');
// }

// public function mainCategory()
// {
//     return $this->belongsTo(MainCategory::class, 'main_category_id', 'id');
// }

  public function subcategory()
    {
        return $this->belongsTo(SubCategory::class, 'subcategory_id');
    }

    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
    }

     public function ledgerEntries()
    {
        return $this->hasMany(Ledger::class, 'journal_id');
    }

public function createdBy()
{
    return $this->belongsTo(\App\Models\User::class, 'created_by');
}

public function journalEntries()
{
    return $this->hasMany(JournalEntries::class, 'chart_of_account_id', 'id');
}

}
