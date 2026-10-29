<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

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
     * Newest first.
     *
     * @unauthenticated
     *
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, total: int}}
     */
    public function index(Request $request): array
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        $articles = Article::published()->latest('published_at')->latest('id')->paginate(self::PER_PAGE);

        return [
            'data' => $articles->getCollection()->map(fn (Article $article) => $this->card($article))->values()->all(),
            'meta' => ['current_page' => $articles->currentPage(), 'last_page' => $articles->lastPage(), 'total' => $articles->total()],
        ];
    }

    /**
     * One article.
     *
     * `body_html` is the article as safe HTML (raw HTML in the text is escaped), to show in a web view or an
     * HTML renderer. `more` has 3 other recent articles.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function show(Article $article): array
    {
        abort_unless($article->isPublished(), 404);

        return ['data' => $this->card($article) + [
            'body_html' => $article->bodyHtml()->toHtml(),
            'more' => Article::published()->whereKeyNot($article->id)->latest('published_at')->limit(3)->get()
                ->map(fn (Article $other) => $this->card($other))->values()->all(),
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private function card(Article $article): array
    {
        return [
            'slug' => $article->slug,
            'title' => $article->title,
            'summary' => $article->summary(),
            'author' => $article->author_name,
            'cover_url' => $article->coverUrl(),
            'reading_minutes' => $article->readingMinutes(),
            'published_at' => $article->published_at?->toIso8601String(),
            'web_url' => route('guide.articles.show', $article),
        ];
    }
}
