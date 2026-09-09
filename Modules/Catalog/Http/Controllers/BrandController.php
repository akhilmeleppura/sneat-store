<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount('products')->get();
        return view('catalog::brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'website'     => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $slug = Str::slug($validated['name']);
        $count = Brand::withoutTenancy()->where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        $validated['slug'] = $slug;

        Brand::create($validated);

        return redirect()->route('catalog.brands.index')
            ->with('success', "Brand '{$validated['name']}' created successfully.");
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        $name = $brand->name;
        $brand->delete();

        return redirect()->route('catalog.brands.index')
            ->with('success', "Brand '{$name}' deleted successfully.");
    }
}
