<?php

namespace Modules\Accounting\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\LedgerFactory;

class Ledger extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
     // Disable default timestamps (created_at, updated_at)
    protected $table = 'accounting_ledgers';

    protected $fillable = [
        'journal_id',
        'transaction_data',
        'description',
        'credit_amount',
        'debit_amount',
        'total_credit',
        'total_debit',
        'balance'
    ];

    public $timestamps = true;

    /**
     * Relation: Ledger belongs to JournalIndex
     */
// public function journalEntry()
// {
//     return $this->belongsTo(JournalEntries::class, 'journal_id', 'id');
// }


public function journalEntry() {
    return $this->belongsTo(JournalEntries::class, 'journal_id');
}

public function accountChart()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

public function journal()
{
    return $this->belongsTo(JournalIndex::class, 'journal_id');
}
// public function chartOfAccount()
// {
//     return $this->belongsTo(AccountChart::class, 'chart_of_account_id');
// }


}
