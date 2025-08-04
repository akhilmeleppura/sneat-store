<?php

namespace  Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\App\Models\Prefix;

class PrefixController extends Controller
{
    /**
     * Display a listing of Prefix Journals.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $menu = [
            [
                'label' => 'Chart of Accounts',
                'icon' => 'bx bx-store-alt',
                'url' => url('/accounting/charts-of-account'),
                'active' => false,
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
                'url' => 'javascript:void(0);',
                'active' => true,
            ]
        ];

        return view('accounting::accounting.prefix', compact('menu'));
    }

    /**
     * Show the form for creating a new Prefix.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('accounting::create');
    }

    /**
     * Store a newly created Prefix in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_name' => 'required|string|unique:accounting_prefixes,journal_name|max:255',
        ]);

        Prefix::create($validated);

        return redirect()->route('accounting.prefix.index')->with('success', 'Prefix saved successfully!');
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
