<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Actions\SaveArticle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Hiker's Guide articles.
 */
class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage', Article::class);

        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();

        return view('admin.articles.index', [
            'articles' => Article::query()
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")))
                ->when($status === 'published', fn ($q) => $q->published())
                ->when($status === 'scheduled', fn ($q) => $q->where('published_at', '>', now()))
                ->when($status === 'draft', fn ($q) => $q->whereNull('published_at'))
                // Drafts first (they need work), then newest.
                ->orderByRaw('published_at is not null')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(config('cms.per_page'))
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage', Article::class);

        return view('admin.articles.create', ['article' => new Article(['author_name' => $request->user()?->name])]);
    }

    public function store(ArticleRequest $request, SaveArticle $action): RedirectResponse
    {
        Gate::authorize('manage', Article::class);

        $article = $action->handle($request->toData());

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Article created.');
    }

    public function edit(Article $article): View
    {
        Gate::authorize('manage', Article::class);

        return view('admin.articles.edit', ['article' => $article]);
    }

    public function update(ArticleRequest $request, Article $article, SaveArticle $action): RedirectResponse
    {
        Gate::authorize('manage', Article::class);

        $action->handle($request->toData(), $article);

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Article saved.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('manage', Article::class);

        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Article deleted.');
    }
}
