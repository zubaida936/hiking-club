<?php

use App\Domain\Identity\Enums\Role;
use App\Models\Album;
use App\Models\AlbumPhoto;
use App\Models\Article;
use App\Models\Event;
use App\Models\HeroSlide;
use App\Models\TeamMember;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->editor = userWithRole(Role::Editor);
});

function articleForm(array $overrides = []): array
{
    return [
        'title' => 'How much water to carry',
        'slug' => '',
        'excerpt' => '',
        'body' => "Half a litre per hour.\n\n## In summer\n\nMore.",
        'author_name' => 'Maya Haddad',
        'is_published' => '1',
        'published_at' => '',
        ...$overrides,
    ];
}

describe('access', function () {
    it('is open to content editors', function () {
        foreach (['admin.articles.index', 'admin.articles.create', 'admin.albums.index', 'admin.albums.create', 'admin.hero-slides.index', 'admin.hero-slides.create', 'admin.team.index', 'admin.team.create'] as $route) {
            $this->actingAs($this->editor)->get(route($route))->assertOk();
        }

        $this->actingAs($this->editor)->get(route('admin.dashboard'))
            ->assertSee(route('admin.articles.index'))
            ->assertSee(route('admin.albums.index'))
            ->assertSee(route('admin.hero-slides.index'))
            ->assertSee(route('admin.team.index'))
            ->assertDontSee(route('admin.subscribers.index'));
    });

    it('is closed to guides', function () {
        $guide = userWithRole(Role::Guide);
        $article = Article::factory()->create();

        foreach (['admin.articles.index', 'admin.albums.index', 'admin.hero-slides.index', 'admin.team.index'] as $route) {
            $this->actingAs($guide)->get(route($route))->assertForbidden();
        }
        $this->actingAs($guide)->delete(route('admin.articles.destroy', $article))->assertForbidden();
        expect(Article::count())->toBe(1);
    });
});

describe('articles', function () {
    it('creates a published article with a cover photo', function () {
        $this->actingAs($this->editor)->post(route('admin.articles.store'), articleForm([
            'cover' => UploadedFile::fake()->image('peak.jpg', 3000, 2000),
        ]))->assertRedirect();

        $article = Article::sole();
        expect($article)
            ->slug->toBe('how-much-water-to-carry')
            ->author_name->toBe('Maya Haddad')
            ->excerpt->toBeNull()
            ->and($article->isPublished())->toBeTrue()
            ->and($article->cover_path)->toStartWith('articles/')->toEndWith('.jpg');
        Storage::disk('public')->assertExists($article->cover_path);
        expect(getimagesizefromstring(Storage::disk('public')->get($article->cover_path))[0])->toBe(1600);

        $this->actingAs($this->editor)->get(route('admin.articles.index'))->assertSee('How much water to carry')->assertSee('Published');
    });

    it('saves drafts and schedules future articles', function () {
        $this->travelTo('2026-11-01 10:00');

        $this->actingAs($this->editor)->post(route('admin.articles.store'), articleForm(['title' => 'Draft one', 'is_published' => '0', 'published_at' => '2026-11-20']));
        $this->actingAs($this->editor)->post(route('admin.articles.store'), articleForm(['title' => 'Later one', 'published_at' => '2026-11-20']));
        $this->actingAs($this->editor)->post(route('admin.articles.store'), articleForm(['title' => 'Today one', 'published_at' => '2026-11-01']));

        expect(Article::where('title', 'Draft one')->sole()->published_at)->toBeNull()
            ->and(Article::where('title', 'Later one')->sole()->isScheduled())->toBeTrue()
            ->and(Article::where('title', 'Today one')->sole()->isPublished())->toBeTrue()
            ->and(Article::published()->pluck('title')->all())->toBe(['Today one']);

        // The date is a day in the club's timezone (Beirut, UTC+2 in November).
        expect(Article::where('title', 'Later one')->sole()->published_at->toDateTimeString())->toBe('2026-11-19 22:00:00');

        $this->actingAs($this->editor)->get(route('admin.articles.index', ['status' => 'scheduled']))->assertSee('Later one')->assertDontSee('Draft one');
        $this->actingAs($this->editor)->get(route('admin.articles.index', ['status' => 'draft']))->assertSee('Draft one')->assertDontSee('Today one');
    });

    it('keeps the slug and publish date, replaces and removes the cover', function () {
        $this->actingAs($this->editor)->post(route('admin.articles.store'), articleForm(['cover' => UploadedFile::fake()->image('a.jpg')]));
        $article = Article::sole();
        $first = $article->cover_path;
        $publishedAt = $article->published_at;

        $this->travel(2)->days();
        $this->actingAs($this->editor)->put(route('admin.articles.update', $article), articleForm([
            'title' => 'Renamed article',
            'cover' => UploadedFile::fake()->image('b.png'),
        ]))->assertRedirect(route('admin.articles.edit', $article));

        $article->refresh();
        expect($article->slug)->toBe('how-much-water-to-carry')
            ->and($article->title)->toBe('Renamed article')
            ->and($article->published_at->equalTo($publishedAt))->toBeTrue()
            ->and($article->cover_path)->not->toBe($first);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($article->cover_path);

        $second = $article->cover_path;
        $this->actingAs($this->editor)->put(route('admin.articles.update', $article), articleForm(['remove_cover' => '1']));
        expect($article->fresh()->cover_path)->toBeNull();
        Storage::disk('public')->assertMissing($second);
    });

    it('validates the form', function () {
        Article::factory()->create(['slug' => 'taken']);

        $this->actingAs($this->editor)->post(route('admin.articles.store'), articleForm([
            'title' => '', 'body' => '', 'author_name' => '', 'slug' => 'taken', 'published_at' => 'soon',
            'cover' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors(['title', 'body', 'author_name', 'slug', 'published_at', 'cover']);

        expect(Article::count())->toBe(1);
    });

    it('deletes the article and its cover photo', function () {
        $this->actingAs($this->editor)->post(route('admin.articles.store'), articleForm(['cover' => UploadedFile::fake()->image('a.jpg')]));
        $article = Article::sole();

        $this->actingAs($this->editor)->delete(route('admin.articles.destroy', $article))->assertRedirect(route('admin.articles.index'));

        expect(Article::count())->toBe(0);
        Storage::disk('public')->assertMissing($article->cover_path);
    });

    it('renders Markdown without letting raw HTML or script links through', function () {
        $article = Article::factory()->make(['body' => "## Water\n\n**Half** a litre <script>alert(1)</script> [bad](javascript:alert(1)) [good](https://example.com)"]);

        $html = $article->bodyHtml()->toHtml();

        expect($html)->toContain('<h2>Water</h2>', '<strong>Half</strong>', '&lt;script&gt;', 'href="https://example.com"')
            ->not->toContain('<script>', 'javascript:')
            ->and($article->summary(27))->toBe('Water Half a litre <script>...')
            ->and(Article::factory()->make(['excerpt' => 'Short.'])->summary())->toBe('Short.');
    });
});

describe('gallery', function () {
    it('creates an album for a hike with photos, prefilled from the event', function () {
        $event = Event::factory()->published()->past()->create(['title' => 'Qadisha Valley Descent']);

        $this->actingAs($this->editor)->get(route('admin.albums.create', ['event' => $event->id]))
            ->assertOk()->assertSee('value="Qadisha Valley Descent"', false);

        $this->actingAs($this->editor)->post(route('admin.albums.store'), [
            'title' => 'Qadisha Valley Descent',
            'event_id' => $event->id,
            'taken_on' => $event->starts_on->toDateString(),
            'is_published' => '1',
            'photos' => [UploadedFile::fake()->image('1.jpg', 4000, 3000), UploadedFile::fake()->image('2.png', 800, 600)],
        ])->assertRedirect();

        $album = Album::sole();
        expect($album)->slug->toBe('qadisha-valley-descent')->event_id->toBe($event->id)->is_published->toBeTrue()
            ->and($album->photos)->toHaveCount(2);
        $album->photos->each(fn (AlbumPhoto $photo) => Storage::disk('public')->assertExists($photo->path));
        expect(getimagesizefromstring(Storage::disk('public')->get($album->photos->first()->path))[0])->toBe(1920);

        $this->actingAs($this->editor)->get(route('admin.albums.index'))->assertSee('Qadisha Valley Descent')->assertSee('2 photos');
    });

    it('adds photos at the end, removes photos and changes the cover', function () {
        $album = Album::factory()->create();
        $this->actingAs($this->editor)->put(route('admin.albums.update', $album), [
            'title' => $album->title, 'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]);
        [$a, $b] = $album->fresh()->photos->all();

        $this->actingAs($this->editor)->put(route('admin.albums.update', $album), [
            'title' => $album->title,
            'photos' => [UploadedFile::fake()->image('c.jpg')],
            'remove_photos' => [$a->id],
        ])->assertRedirect(route('admin.albums.edit', $album));

        $photos = $album->fresh()->photos;
        expect($photos->pluck('id')->first())->toBe($b->id)->and($photos)->toHaveCount(2);
        Storage::disk('public')->assertMissing($a->path);

        $c = $photos->last();
        $this->actingAs($this->editor)->put(route('admin.albums.update', $album), ['title' => $album->title, 'cover_photo' => $c->id]);
        expect($album->fresh()->photos->pluck('id')->all())->toBe([$c->id, $b->id]);
    });

    it('limits photos per upload and keeps other albums photos safe', function () {
        $album = Album::factory()->create();
        $other = Album::factory()->create();
        $foreign = $other->photos()->create(['path' => 'albums/other.jpg']);

        $this->actingAs($this->editor)->put(route('admin.albums.update', $album), [
            'title' => $album->title,
            'photos' => array_map(fn ($i) => UploadedFile::fake()->image("{$i}.jpg", 10, 10), range(1, 21)),
        ])->assertSessionHasErrors('photos');

        $this->actingAs($this->editor)->put(route('admin.albums.update', $album), ['title' => $album->title, 'remove_photos' => [$foreign->id]]);
        expect($foreign->fresh())->not->toBeNull();
    });

    it('deletes the album with its photo files', function () {
        $album = Album::factory()->create();
        $this->actingAs($this->editor)->put(route('admin.albums.update', $album), ['title' => $album->title, 'photos' => [UploadedFile::fake()->image('a.jpg')]]);
        $path = $album->fresh()->photos->first()->path;

        $this->actingAs($this->editor)->delete(route('admin.albums.destroy', $album))->assertRedirect(route('admin.albums.index'));

        expect(Album::count())->toBe(0)->and(AlbumPhoto::count())->toBe(0);
        Storage::disk('public')->assertMissing($path);
    });
});

describe('homepage slides', function () {
    it('adds a slide with a background photo and a button', function () {
        $this->actingAs($this->editor)->post(route('admin.hero-slides.store'), [
            'eyebrow' => '🍂 Autumn', 'heading' => 'Chase the colours', 'text' => 'Golden forests.',
            'cta_label' => 'See hikes', 'cta_url' => '/events', 'position' => '1', 'is_active' => '1',
            'image' => UploadedFile::fake()->image('hero.jpg', 2400, 1350),
        ])->assertRedirect(route('admin.hero-slides.index'));

        $slide = HeroSlide::sole();
        expect($slide)->heading->toBe('Chase the colours')->cta_url->toBe('/events')->is_active->toBeTrue()
            ->and($slide->ctaHref())->toBe(url('/events'));
        Storage::disk('public')->assertExists($slide->image_path);

        $this->actingAs($this->editor)->get(route('admin.hero-slides.index'))->assertSee('Chase the colours')->assertSee('Shown');
        $this->get('/')->assertSee('Chase the colours')->assertSee($slide->imageUrl());
    });

    it('only accepts links to this site or to http(s) pages, and a button needs both fields', function (array $fields, array $errors) {
        $this->actingAs($this->editor)->post(route('admin.hero-slides.store'), ['heading' => 'Slide', 'position' => '1', ...$fields])
            ->assertSessionHasErrors($errors);

        expect(HeroSlide::count())->toBe(0);
    })->with([
        'javascript' => [['cta_label' => 'Go', 'cta_url' => 'javascript:alert(1)'], ['cta_url']],
        'other site without scheme' => [['cta_label' => 'Go', 'cta_url' => '//evil.example'], ['cta_url']],
        'label only' => [['cta_label' => 'Go'], ['cta_url']],
        'link only' => [['cta_url' => '/shop'], ['cta_label']],
    ]);

    it('replaces the photo, hides and deletes slides', function () {
        $this->actingAs($this->editor)->post(route('admin.hero-slides.store'), ['heading' => 'Slide', 'position' => '1', 'is_active' => '1', 'image' => UploadedFile::fake()->image('a.jpg')]);
        $slide = HeroSlide::sole();
        $first = $slide->image_path;

        $this->actingAs($this->editor)->put(route('admin.hero-slides.update', $slide), ['heading' => 'Slide', 'position' => '1', 'image' => UploadedFile::fake()->image('b.jpg')]);
        $slide->refresh();
        expect($slide->is_active)->toBeFalse()->and($slide->image_path)->not->toBe($first);
        Storage::disk('public')->assertMissing($first);

        $this->actingAs($this->editor)->delete(route('admin.hero-slides.destroy', $slide))->assertRedirect(route('admin.hero-slides.index'));
        expect(HeroSlide::count())->toBe(0);
        Storage::disk('public')->assertMissing($slide->image_path);
    });
});

describe('team', function () {
    it('adds, edits and removes team members', function () {
        $this->actingAs($this->editor)->post(route('admin.team.store'), [
            'name' => 'Rami Khoury', 'role' => 'Founder & lead guide', 'bio' => 'Walked the whole LMT.', 'position' => '1', 'is_active' => '1',
            'photo' => UploadedFile::fake()->image('rami.jpg', 1200, 1200),
        ])->assertRedirect(route('admin.team.index'));

        $member = TeamMember::sole();
        expect(getimagesizefromstring(Storage::disk('public')->get($member->photo_path))[0])->toBe(800);
        $this->actingAs($this->editor)->get(route('admin.team.index'))->assertSee('Rami Khoury')->assertSee('Founder &amp; lead guide', false);

        $this->actingAs($this->editor)->put(route('admin.team.update', $member), ['name' => 'Rami Khoury', 'role' => 'Lead guide', 'position' => '2', 'is_active' => '1', 'remove_photo' => '1']);
        expect($member->fresh())->role->toBe('Lead guide')->position->toBe(2)->photo_path->toBeNull();
        Storage::disk('public')->assertMissing($member->photo_path);

        $this->actingAs($this->editor)->put(route('admin.team.update', $member), ['name' => '', 'role' => '', 'position' => '-1'])
            ->assertSessionHasErrors(['name', 'role', 'position']);

        $this->actingAs($this->editor)->delete(route('admin.team.destroy', $member))->assertRedirect(route('admin.team.index'));
        expect(TeamMember::count())->toBe(0);
    });

    it('lists active members in order', function () {
        TeamMember::factory()->create(['name' => 'Second', 'position' => 2]);
        TeamMember::factory()->create(['name' => 'First', 'position' => 1]);
        TeamMember::factory()->create(['name' => 'Hidden', 'is_active' => false]);

        expect(TeamMember::shown()->pluck('name')->all())->toBe(['First', 'Second']);
    });
});
