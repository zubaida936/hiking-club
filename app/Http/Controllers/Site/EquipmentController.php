<?php

namespace App\Http\Controllers\Site;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\View\View;

/**
 * "What You Need for Each Adventure": gear checklists from config/equipment.php,
 * per difficulty level for hiking, with links to the shop.
 */
class EquipmentController extends Controller
{
    public function __invoke(): View
    {
        return view('site.equipment', [
            'adventures' => config('equipment.adventures'),
            'levels' => DifficultyLevel::cases(),
            // Only categories that have something to buy get a "Shop" link.
            'shopCategories' => ProductCategory::query()
                ->whereHas('products', fn ($q) => $q->where('is_active', true))
                ->pluck('name', 'slug')
                ->all(),
        ]);
    }
}
