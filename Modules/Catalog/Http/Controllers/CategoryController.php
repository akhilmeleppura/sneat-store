<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')->withCount('products')->get();
        return view('catalog::categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'parent_id'   => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $slug = Str::slug($validated['name']);
        $count = Category::withoutTenancy()->where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        $validated['slug'] = $slug;

        Category::create($validated);

        return redirect()->route('catalog.categories.index')
            ->with('success', "Category '{$validated['name']}' created successfully.");
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $name = $category->name;
        $category->delete();

        return redirect()->route('catalog.categories.index')
            ->with('success', "Category '{$name}' deleted successfully.");
    }
}
