<?php

namespace Modules\Accounting\Listeners;

use Modules\Accounting\app\Events\EntryCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCumulativeBalance
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
 public function handle(EntryCreated $event)
    {
        $entry = $event->entry;
        $chartOfAccount = $entry->chartOfAccount;

        $cumulativeDebit = $chartOfAccount->journalEntries()->sum('debit_amount') + 
                           $chartOfAccount->openingBalances()->sum('debit_amount');

        $cumulativeCredit = $chartOfAccount->journalEntries()->sum('credit_amount') + 
                            $chartOfAccount->openingBalances()->sum('credit_amount');

        $chartOfAccount->update([
            'cumulative_debit' => $cumulativeDebit,
            'cumulative_credit' => $cumulativeCredit,
        ]);
    }
}
