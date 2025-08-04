<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\App\Models\MainCategory;
use Modules\Accounting\App\Models\SubCategory;
use Modules\Accounting\App\Models\ChartOfAccount;

class AccountingController extends Controller
{
    /**
     * Display the main accounting dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $menu = [
            [
                'label' => 'Chart of Accounts',
                'icon' => 'bx bx-store-alt',
                'url' => 'javascript:void(0);',
                'active' => true,
            ],
            [
                'label' => 'Sub Category',
                'icon' => 'bx bx-credit-card',
                'url' => url('/accounting/subcategory'),
                'active' => false,
            ],
            [
                'label' => 'Prefix Journal',
                'icon' => 'bx bx-credit-card',
                'url' => url('/accounting/prefix'),
                'active' => false,
            ]
        ];

        $mainCategories = MainCategory::all();
        $subcategories = SubCategory::all();
        $accounts = ChartOfAccount::with(['mainCategory', 'subCategory'])->get();

        return view('accounting::accounting.accounting', compact('menu', 'mainCategories', 'subcategories', 'accounts'));
    }

    /**
     * Show the form for creating a new account chart.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('accounting::create');
    }

    /**
     * Store a newly created account chart in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'main_category_id' => 'required|exists:accounting_main_categories,id',
            'subcategory_id' => 'required|exists:accounting_subcategories,id',
            'account_name' => 'required|string|max:255',
            'opening_balance' => 'required|numeric'
        ]);

        ChartOfAccount::create([
            'main_category_id' => $validated['main_category_id'],
            'subcategory_id' => $validated['subcategory_id'],
            'account_name' => $validated['account_name'],
            'opening_balance' => $validated['opening_balance'],
            'status' => true
        ]);

        return redirect()->route('accounting.index')->with('success', 'Account created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        return view('accounting::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        return view('accounting::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return void
     */
    public function destroy($id) {}
}
