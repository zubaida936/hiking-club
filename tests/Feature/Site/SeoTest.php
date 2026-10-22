<?php

use App\Domain\Identity\Enums\Role;
use App\Models\Event;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

describe('meta tags', function () {
    it('gives every page a description, canonical URL and share tags', function () {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="description" content="'.e(config('site.description')).'">', false)
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('<meta property="og:title" content="'.e(config('app.name')).'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('images/og-default.jpg').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertDontSee('name="robots"', false);
    });

    it('lets a page set its own description, share image and type', function () {
        $html = Blade::render(<<<'BLADE'
            <x-site.layout title="Qadisha Valley Descent" description="A steep descent into the holy valley." image="https://cdn.example.com/qadisha.jpg" type="article">x</x-site.layout>
            BLADE);

        expect($html)
            ->toContain('<title>Qadisha Valley Descent · '.e(config('app.name')).'</title>')
            ->toContain('<meta name="description" content="A steep descent into the holy valley.">')
            ->toContain('<meta property="og:title" content="Qadisha Valley Descent">')
            ->toContain('<meta property="og:type" content="article">')
            ->toContain('<meta property="og:image" content="https://cdn.example.com/qadisha.jpg">');
    });

    it('turns a relative share image into an absolute URL and shortens long descriptions', function () {
        $html = Blade::render('<x-site.layout image="storage/events/a.jpg" :description="str_repeat(\'word \', 60)">x</x-site.layout>');

        expect($html)->toContain('content="'.asset('storage/events/a.jpg').'"')
            ->and(preg_match('#<meta name="description" content="([^"]*)">#', $html, $m))->toBe(1)
            ->and(mb_strlen(html_entity_decode($m[1])))->toBeLessThanOrEqual(163);
    });

    it('keeps placeholder and newsletter pages out of search results', function (string $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertDontSee('rel="canonical"', false);
    })->with([
        'coming soon page' => fn () => route('about'),
        'unsubscribe page' => fn () => route('newsletter.unsubscribe', Subscriber::factory()->create()->token),
    ]);

    it('describes the club as structured data on the homepage', function () {
        config()->set('site.social', ['facebook' => 'fb.club', 'instagram' => '', 'tiktok' => '', 'youtube' => '']);

        $html = $this->get('/')->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $data = json_decode($m[1], true);

        expect($data)
            ->toMatchArray(['@type' => 'SportsClub', 'name' => config('app.name'), 'url' => route('home')])
            ->and($data['sameAs'])->toBe(['https://www.facebook.com/fb.club']);
    });

    it('escapes structured data so it cannot break out of the script tag', function () {
        config()->set('site.description', '</script><script>alert(1)</script>');

        expect($this->get('/')->getContent())->not->toContain('</script><script>alert(1)');
    });
});

describe('sitemap.xml', function () {
    it('lists the real pages but not placeholders', function () {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.route('home').'</loc>', false)
            ->assertDontSee(route('about'), false);
    });

    it('lists published events once the event page exists', function () {
        Route::get('events/{event:slug}', fn () => 'event')->name('events.show');
        Route::getRoutes()->refreshNameLookups();

        $published = Event::factory()->published()->create(['slug' => 'qadisha-valley']);
        Event::factory()->create(['slug' => 'draft-hike']);
        Event::factory()->cancelled()->create(['slug' => 'cancelled-hike']);

        $this->get('/sitemap.xml')
            ->assertSee('<loc>'.url('events/qadisha-valley').'</loc>', false)
            ->assertSee('<lastmod>'.$published->updated_at->toAtomString().'</lastmod>', false)
            ->assertDontSee('draft-hike')
            ->assertDontSee('cancelled-hike');
    });

    it('is valid XML', function () {
        $xml = simplexml_load_string($this->get('/sitemap.xml')->getContent());

        expect($xml)->not->toBeFalse()->and($xml->getName())->toBe('urlset');
    });
});

describe('robots.txt', function () {
    it('blocks crawlers outside production', function () {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSeeText("User-agent: *\nDisallow: /\n", false);
    });

    it('allows crawling in production, except the CMS', function () {
        app()->detectEnvironment(fn () => 'production');

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin/', false)
            ->assertSee('Disallow: /newsletter/', false)
            ->assertSee('Sitemap: '.route('sitemap'), false)
            ->assertDontSee("Disallow: /\n", false);
    });
});

describe('analytics', function () {
    it('adds no tracking script without a measurement ID', function () {
        config()->set('site.analytics.ga4_id', null);

        $this->get('/')->assertDontSee('googletagmanager', false);
    });

    it('adds Google Analytics 4 when an ID is set', function () {
        config()->set('site.analytics.ga4_id', 'G-TEST123');

        $this->get('/')
            ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST123', false)
            ->assertSee("gtag('config', 'G-TEST123')", false);
    });

    it('never tracks the CMS', function () {
        config()->set('site.analytics.ga4_id', 'G-TEST123');

        $this->actingAs(userWithRole(Role::Admin))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('googletagmanager', false);
    });
});
