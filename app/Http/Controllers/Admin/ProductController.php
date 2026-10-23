<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Shop\Actions\SaveProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $search = $request->string('q')->trim()->toString();
        $category = $request->integer('category') ?: null;

        return view('admin.products.index', [
            'products' => Product::query()
                ->with(['category', 'images'])
                ->withSum('variants', 'stock')
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->when($category, fn ($q) => $q->where('product_category_id', $category))
                ->orderBy('name')
                ->paginate(config('cms.per_page'))
                ->withQueryString(),
            'categories' => ProductCategory::orderBy('position')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage', Product::class);

        return view('admin.products.create', [
            'product' => new Product(['is_active' => true]),
            'categories' => ProductCategory::orderBy('position')->get(),
        ]);
    }

    public function store(ProductRequest $request, SaveProduct $action): RedirectResponse
    {
        Gate::authorize('manage', Product::class);

        $product = $action->handle($request->toData());

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        Gate::authorize('manage', Product::class);

        return view('admin.products.edit', [
            'product' => $product->load(['variants', 'images']),
            'categories' => ProductCategory::orderBy('position')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product, SaveProduct $action): RedirectResponse
    {
        Gate::authorize('manage', Product::class);

        $action->handle($request->toData(), $product);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product saved.');
    }

    /**
     * Soft delete: past orders keep their copied names; photos stay so it can be restored.
     */
    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('manage', Product::class);

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }
}
