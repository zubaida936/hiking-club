<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $categories = ProductCategory::query()->orderBy('position')->orderBy('name')->get();
        // Unknown categories are ignored rather than rejected: these are shareable URLs.
        $category = $categories->firstWhere('slug', (string) $request->query('category'));

        return view('site.shop.index', [
            'categories' => $categories,
            'category' => $category,
            'products' => Product::active()
                ->when($category, fn ($q) => $q->where('product_category_id', $category?->id))
                ->with(['images', 'category'])
                ->withSum('variants', 'stock')
                ->orderBy('position')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('site.shop.show', ['product' => $product->load(['images', 'variants', 'category'])]);
    }
}
