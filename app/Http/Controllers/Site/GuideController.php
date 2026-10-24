<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hiker's Guide articles (trails, peaks, water needs...), written in the CMS.
 */
class GuideController extends Controller
{
    private const PER_PAGE = 9;

    public function index(): View
    {
        return view('site.guides.index', [
            'articles' => Article::published()->latest('published_at')->latest('id')->paginate(self::PER_PAGE),
        ]);
    }

    public function show(Request $request, Article $article): View
    {
        // Drafts and scheduled articles are only visible to content staff (as a preview).
        $preview = ! $article->isPublished();
        abort_if($preview && ! $request->user()?->can('manage', Article::class), 404);

        return view('site.guides.show', [
            'article' => $article,
            'preview' => $preview,
            'more' => Article::published()->whereKeyNot($article->id)->latest('published_at')->limit(3)->get(),
        ]);
    }
}
