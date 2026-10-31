<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Article;
use App\Models\Event;
use App\Models\LegalPage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SeoController extends Controller
{
    /**
     * Public pages that have real content; add a page's route name here when its feature ships.
     */
    private const PAGES = ['home', 'about', 'events.index', 'events.archive', 'gallery.index', 'x-events', 'leaderboard', 'rank-system', 'guide.difficulty', 'guide.equipment', 'guide.articles', 'contact'];

    public function sitemap(): Response
    {
        /** @var list<array{loc: string, lastmod: ?string}> $urls */
        $urls = [];

        foreach (self::PAGES as $name) {
            if (Route::has($name)) {
                $urls[] = ['loc' => route($name), 'lastmod' => null];
            }
        }

        // Legal pages only once they have text (an empty one is noindex).
        foreach (LegalPage::query()->whereNotNull('body')->where('body', '!=', '')->get() as $page) {
            $urls[] = ['loc' => route($page->document->routeName()), 'lastmod' => $page->updated_at?->toAtomString()];
        }

        foreach (Event::published()->orderByDesc('starts_on')->select(['id', 'slug', 'updated_at'])->lazy() as $event) {
            $urls[] = ['loc' => route('events.show', $event), 'lastmod' => $event->updated_at?->toAtomString()];
        }

        foreach (Article::published()->latest('published_at')->select(['id', 'slug', 'updated_at'])->lazy() as $article) {
            $urls[] = ['loc' => route('guide.articles.show', $article->slug), 'lastmod' => $article->updated_at?->toAtomString()];
        }

        foreach (Album::published()->has('photos')->latest('id')->select(['id', 'slug', 'updated_at'])->lazy() as $album) {
            $urls[] = ['loc' => route('gallery.show', $album->slug), 'lastmod' => $album->updated_at?->toAtomString()];
        }

        return response()
            ->view('site.seo.sitemap', ['urls' => $urls], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Only production may be indexed; staging and local sites ask crawlers to stay out.
     */
    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /'.trim(config('cms.path'), '/').'/', 'Disallow: /newsletter/', 'Disallow: /bookings/', 'Disallow: /events/*/register', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
