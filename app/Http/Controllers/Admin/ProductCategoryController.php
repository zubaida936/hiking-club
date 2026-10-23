<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Shop categories: a short list edited on one page.
 */
class ProductCategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage', ProductCategory::class);

        return view('admin.products.categories', [
            'categories' => ProductCategory::withCount('products')->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage', ProductCategory::class);

        $name = $request->validate(['name' => ['required', 'string', 'max:60']])['name'];
        ProductCategory::create([
            'name' => trim($name),
            'slug' => UniqueSlug::for(ProductCategory::class, $name),
            'position' => (int) ProductCategory::max('position') + 1,
        ]);

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        Gate::authorize('manage', ProductCategory::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'position' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);
        $category->update(['name' => trim($validated['name']), 'position' => (int) $validated['position']]);

        return back()->with('success', 'Category saved.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        Gate::authorize('manage', ProductCategory::class);

        if ($category->products()->withTrashed()->exists()) {
            return back()->with('error', 'Move or delete its products first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
