<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Actions\SaveHeroSlide;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeroSlideRequest;
use App\Models\HeroSlide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The homepage hero carousel.
 */
class HeroSlideController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage', HeroSlide::class);

        return view('admin.hero-slides.index', ['slides' => HeroSlide::orderBy('position')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('manage', HeroSlide::class);

        return view('admin.hero-slides.create', [
            'slide' => new HeroSlide(['is_active' => true, 'position' => (int) HeroSlide::max('position') + 1]),
        ]);
    }

    public function store(HeroSlideRequest $request, SaveHeroSlide $action): RedirectResponse
    {
        Gate::authorize('manage', HeroSlide::class);

        $action->handle($request->toData());

        return redirect()->route('admin.hero-slides.index')->with('success', 'Slide added.');
    }

    public function edit(HeroSlide $heroSlide): View
    {
        Gate::authorize('manage', HeroSlide::class);

        return view('admin.hero-slides.edit', ['slide' => $heroSlide]);
    }

    public function update(HeroSlideRequest $request, HeroSlide $heroSlide, SaveHeroSlide $action): RedirectResponse
    {
        Gate::authorize('manage', HeroSlide::class);

        $action->handle($request->toData(), $heroSlide);

        return redirect()->route('admin.hero-slides.index')->with('success', 'Slide saved.');
    }

    public function destroy(HeroSlide $heroSlide): RedirectResponse
    {
        Gate::authorize('manage', HeroSlide::class);

        $heroSlide->delete();

        return redirect()->route('admin.hero-slides.index')->with('success', 'Slide deleted.');
    }
}
