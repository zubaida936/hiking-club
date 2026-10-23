<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SeoController extends Controller
{
    /**
     * Public pages that have real content. "Coming soon" placeholders are left out
     * (they are noindex); add a page's route name here when its feature ships.
     */
    private const PAGES = ['home', 'events.index'];

    public function sitemap(): Response
    {
        /** @var list<array{loc: string, lastmod: ?string}> $urls */
        $urls = [];

        foreach (self::PAGES as $name) {
            if (Route::has($name)) {
                $urls[] = ['loc' => route($name), 'lastmod' => null];
            }
        }

        foreach (Event::published()->orderByDesc('starts_on')->select(['id', 'slug', 'updated_at'])->lazy() as $event) {
            $urls[] = ['loc' => route('events.show', $event), 'lastmod' => $event->updated_at?->toAtomString()];
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
            ? ['User-agent: *', 'Disallow: /'.trim(config('cms.path'), '/').'/', 'Disallow: /newsletter/', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
