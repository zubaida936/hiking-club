<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArticleDetailResource;
use App\Http\Resources\Api\V1\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Guide articles (CMS → Guide articles). Only published ones: drafts and scheduled articles give 404
 * (the website's staff preview stays on the website).
 */
class GuideController extends Controller
{
    public const PER_PAGE = 20;

    /**
     * Guide articles.
     *
     * Newest first, 20 per page (`?page=N`).
     *
     * @unauthenticated
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        return ArticleResource::collection(
            Article::published()->latest('published_at')->latest('id')->paginate(self::PER_PAGE),
        );
    }

    /**
     * One article.
     *
     * `body_html` is the article as safe HTML (raw HTML in the text is escaped), to show in a web view or an
     * HTML renderer. `more` has 3 other recent articles.
     *
     * @unauthenticated
     */
    public function show(Article $article): ArticleDetailResource
    {
        abort_unless($article->isPublished(), 404);

        $more = Article::published()->whereKeyNot($article->id)->latest('published_at')->limit(3)->get();

        return (new ArticleDetailResource($article))->withMore($more);
    }
}
