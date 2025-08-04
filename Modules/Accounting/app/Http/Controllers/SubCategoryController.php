<?php

namespace  Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\App\Models\MainCategory;
use Modules\Accounting\App\Models\SubCategory;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of Sub Categories.
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
                'url' => 'javascript:void(0);',
                'active' => true,
            ],
            [
                'label' => 'Prefix Journal',
                'icon' => 'bx bx-credit-card',
                'url' => url('/accounting/prefix'),
                'active' => false,
            ]
        ];

        $subCategories = SubCategory::with('mainCategory')->get();
        $mainCategories = MainCategory::all();

        return view('accounting::accounting.subcategory', compact('menu', 'mainCategories', 'subCategories'));
    }

    /**
     * Show the form for creating a new Sub Category.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('accounting::create');
    }

    /**
     * Store a newly created Sub Category in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'main_category_id' => 'required|exists:accounting_main_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        SubCategory::create($validated);

        return redirect()->back()->with('success', 'Subcategory added successfully.');
    }

    /**
     * Display the specified Sub Category.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        return view('accounting::show');
    }

    /**
     * Show the form for editing the specified Sub Category.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        return view('accounting::edit');
    }

    /**
     * Update the specified Sub Category in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified Sub Category from storage.
     *
     * @param int $id
     * @return void
     */
    public function destroy($id) {}
}
