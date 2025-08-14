<?php

namespace Modules\Accounting\App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\App\Models\Ledger;
use Yajra\DataTables\DataTables;
use Modules\Accounting\App\Models\ChartOfAccount;
use Modules\Accounting\App\Models\OpeningBalance;
use Modules\Accounting\App\Models\journalentries;
use Illuminate\Pagination\LengthAwarePaginator;



class LedgerController extends Controller
{
    /**
     * Display the Ledger listing page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('accounting::ledger.ledger');
    }



public function entriesList(Request $request)
{
    $query = ChartOfAccount::with(['subcategory', 'mainCategory']);

    // Search filter
    $searchValue = $request->input('search.value');
    if (!empty($searchValue)) {
        $query->where('account_name', 'like', "%{$searchValue}%")
              ->orWhereHas('subcategory', function ($subQ) use ($searchValue) {
                  $subQ->where('name', 'like', "%{$searchValue}%");
              })
              ->orWhereHas('mainCategory', function ($mainQ) use ($searchValue) {
                  $mainQ->where('name', 'like', "%{$searchValue}%");
              });
    }

    $totalRecords = ChartOfAccount::count();
    $filteredRecords = $query->count();

    $chartAccounts = $query
        ->offset($request->input('start'))
        ->limit($request->input('length'))
        ->get();

    $data = $chartAccounts->map(function ($account) {
        return [
            'id'            => $account->id,
            'account_name'  => $account->account_name,
            'sub_category'  => $account->subcategory?->name ?? '-',
            'main_category' => $account->mainCategory?->name ?? '-',
        ];
    });

    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);
}


    /**
     * Display Ledger details including cumulative credits/debits.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
 
// public function details($id)
// {
//     $accountChart = ChartOfAccount::with([
//         'mainCategory',
//         'subcategory',
//         'journalEntries.journal.ledger',
//         'journalEntries.journal.creator'
//     ])->findOrFail($id);

//     // Fetch Opening Balance Data
//     $openingBalance = OpeningBalance::where('chart_of_account_id', $id)->first();

//     $openingCredit = $openingBalance->credit_amount ?? 0;
//     $openingDebit = $openingBalance->debit_amount ?? 0;

//     // Initialize cumulative totals with Opening Balance
//     $cumulativeCredit = $openingCredit;
//     $cumulativeDebit = $openingDebit;

//     // Prepare Opening Balance Row (Always First Row)
//     $openingRow = (object)[
//         'type'                => 'chart',
//         'id'                  => $openingBalance->id ?? null,
//         'created_at'          => $openingBalance->created_at ?? now(),
//         'entry_date'          => $openingBalance->created_at ?? now(),
//         'due_date'            => null,
//         'description'         => $openingBalance->description ?? 'Opening Balance',
//         'client_name'         => $accountChart->account_name,
//         'credit'              => $openingCredit,
//         'debit'               => $openingDebit,
//         'cumulative_credit'   => $openingCredit,
//         'cumulative_debit'    => $openingDebit,
//         'balance'             => $openingCredit - $openingDebit,
//         'created_by'          => '-', 
//     ];

//     // Prepare Ledger Entries
//     $ledgerData = $accountChart->journalEntries->map(function($entry) use (&$cumulativeCredit, &$cumulativeDebit) {
//         $journal = $entry->journal;

//         $credit = (float) $entry->credit_amount;
//         $debit  = (float) $entry->debit_amount;

//         $cumulativeCredit += $credit;
//         $cumulativeDebit  += $debit;

//         return (object)[
//             'type'                => 'entry',
//             'id'                  => $entry->id,
//             'created_at'          => $entry->created_at,
//             'entry_date'          => $journal->transaction_date ?? $entry->created_at,
//             'due_date'            => null,
//             'description'         => $entry->description,
//             'client_name'         => '-',
//             'credit'              => $credit,
//             'debit'               => $debit,
//             'cumulative_credit'   => $cumulativeCredit,
//             'cumulative_debit'    => $cumulativeDebit,
//             'balance'             => $cumulativeCredit - $cumulativeDebit,
//             'created_by'          => $journal->creator->name ?? '-'
//         ];
//     });

//     // Sort only ledger entries by created_at
//     $sortedLedgerData = $ledgerData->sortBy('created_at')->values();

//     // Combine: Opening Balance Row + Sorted Ledger Entries
//     $combinedData = collect()->push($openingRow)->merge($sortedLedgerData);
//  $perPage = 10; // Items per page
//     $currentPage = LengthAwarePaginator::resolveCurrentPage();
//     $pagedData = $combinedData->slice(($currentPage - 1) * $perPage, $perPage)->values();

//     $paginatedData = new LengthAwarePaginator(
//         $pagedData,
//         $combinedData->count(),
//         $perPage,
//         $currentPage,
//         ['path' => request()->url(), 'query' => request()->query()]
//     );

//     return view('accounting::ledger.details', compact('accountChart', 'paginatedData'));
// }


public function details($id)
{
    $accountChart = ChartOfAccount::with([
        'mainCategory',
        'subcategory',
        'journalEntries.journal.creator'
    ])->findOrFail($id);

    $openingBalance = OpeningBalance::where('chart_of_account_id', $id)->first();

    $openingCredit = $openingBalance->credit_amount ?? 0;
    $openingDebit = $openingBalance->debit_amount ?? 0;

    $cumulativeCredit = $openingCredit;
    $cumulativeDebit = $openingDebit;

    // Optional date range
    $dateRange = request()->input('date_range');

    $ledgerEntriesQuery = $accountChart->journalEntries();

    if ($dateRange) {
        [$startDate, $endDate] = explode(' to ', $dateRange);
        $ledgerEntriesQuery->whereBetween('created_at', [$startDate, $endDate]);
    }

    $ledgerData = $ledgerEntriesQuery->get()->map(function($entry) use (&$cumulativeCredit, &$cumulativeDebit) {
        $credit = (float) $entry->credit_amount;
        $debit  = (float) $entry->debit_amount;

        $cumulativeCredit += $credit;
        $cumulativeDebit  += $debit;

        return (object)[
            'type'              => 'entry',
            'id'                => $entry->id,
            'created_at'        => $entry->created_at,
            'entry_date'        => $entry->created_at,
            'due_date'           => null,
            'description'       => $entry->description,
            'client_name'       => '-',
            'credit'            => $credit,
            'debit'             => $debit,
            'cumulative_credit' => $cumulativeCredit,
            'cumulative_debit'  => $cumulativeDebit,
            'balance'           => $cumulativeCredit - $cumulativeDebit,
            'created_by'        => $entry->journal->creator->name ?? '-'
        ];
    });

   // Include Opening Balance only if within filter range
$openingRow = (object)[
    'type'              => 'chart',
    'id'                => $openingBalance->id ?? null,
    'created_at'        => $openingBalance->created_at ?? now(),
    'entry_date'        => $openingBalance->created_at ?? now(),
    'due_date'          => null,
    'description'       => $openingBalance->description ?? 'Opening Balance',
    'client_name'       => $accountChart->account_name,
    'credit'            => $openingCredit,
    'debit'             => $openingDebit,
    'cumulative_credit' => $openingCredit,
    'cumulative_debit'  => $openingDebit,
    'balance'           => $openingCredit - $openingDebit,
    'created_by'        => '-'
];

// Apply date filter to opening row
if ($dateRange) {
    [$startDate, $endDate] = explode(' to ', $dateRange);
    $startDate = \Carbon\Carbon::parse($startDate)->startOfDay();
    $endDate = \Carbon\Carbon::parse($endDate)->endOfDay();

    if ($openingRow->entry_date < $startDate) {
        $openingRow = null; // exclude opening balance before filter
    }
}

$combinedData = collect();
if ($openingRow) {
    $combinedData->push($openingRow);
}
$combinedData = $combinedData->merge($ledgerData->sortBy('entry_date')->values());

    // Paginate
    $perPage = 10;
    $page = request()->get('page', 1);
    $paginatedData = new LengthAwarePaginator(
        $combinedData->forPage($page, $perPage),
        $combinedData->count(),
        $perPage,
        $page,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('accounting::ledger.details', [
        'accountChart' => $accountChart,
        'paginatedData' => $paginatedData, // ✅ use the paginator here
        'totals' => [
            'total_credit' => $combinedData->sum('credit'),
            'total_debit' => $combinedData->sum('debit'),
            'total_balance' => $combinedData->sum('credit') - $combinedData->sum('debit'),
        ],
        'dateRange' => $dateRange,
    ]);
}


    /**
     * Show the form for creating a new Ledger entry.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('accounting::create');
    }

    /**
     * Store a newly created Ledger entry.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {}

    /**
     * Display the specified Ledger entry.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        return view('accounting::show');
    }

    /**
     * Show the form for editing the specified Ledger entry.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        return view('accounting::edit');
    }

    /**
     * Update the specified Ledger entry.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified Ledger entry.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {}
}
