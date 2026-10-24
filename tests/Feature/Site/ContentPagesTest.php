<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Registrations\Enums\Attendance;
use App\Mail\ContactMessageMail;
use App\Models\Album;
use App\Models\Article;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Mail;

describe('guides', function () {
    it('lists published articles newest first, with date, author and Read More', function () {
        $this->travelTo('2026-11-10 12:00');
        Article::factory()->create(['title' => 'Older guide', 'author_name' => 'Rami Khoury', 'published_at' => '2026-11-01 08:00']);
        Article::factory()->create(['title' => 'Newer guide', 'excerpt' => 'Water rules of thumb.', 'published_at' => '2026-11-08 08:00']);
        Article::factory()->draft()->create(['title' => 'Draft guide']);
        Article::factory()->scheduled()->create(['title' => 'Future guide']);

        $this->get(route('guide.articles'))
            ->assertOk()
            ->assertSeeInOrder(['8 Nov 2026', 'Newer guide', 'Water rules of thumb.', 'Read More', '1 Nov 2026', 'Rami Khoury', 'Older guide'])
            ->assertSee(route('guide.articles.show', 'older-guide'))
            ->assertDontSee('Draft guide')
            ->assertDontSee('Future guide')
            ->assertDontSee('noindex');
    });

    it('paginates and has a friendly empty state', function () {
        $this->get(route('guide.articles'))->assertOk()->assertSee('Our first guides are being written.');

        Article::factory()->count(10)->create();
        $this->get(route('guide.articles'))->assertSee(route('guide.articles', ['page' => 2]));
        $this->get(route('guide.articles', ['page' => 2]))->assertOk()->assertSee('noindex');
    });

    it('shows an article with its Markdown text, reading time and structured data', function () {
        $article = Article::factory()->create([
            'title' => 'How much water to carry',
            'author_name' => 'Maya Haddad',
            'body' => "## Before you leave\n\nDrink **a full glass**. <script>alert(1)</script>",
            'cover_path' => 'articles/cover.jpg',
        ]);
        Article::factory()->create(['title' => 'Another guide']);

        $this->get(route('guide.articles.show', $article->slug))
            ->assertOk()
            ->assertSee('<h2>Before you leave</h2>', false)
            ->assertSee('<strong>a full glass</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('By Maya Haddad')
            ->assertSee('1 min read')
            ->assertSee('"@type":"Article"', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee($article->coverUrl())
            ->assertSee('More guides')
            ->assertSee('Another guide');
    });

    it('hides drafts and scheduled articles from visitors but lets content staff preview them', function () {
        $draft = Article::factory()->draft()->create();
        $scheduled = Article::factory()->scheduled()->create();

        $this->get(route('guide.articles.show', $draft->slug))->assertNotFound();
        $this->get(route('guide.articles.show', $scheduled->slug))->assertNotFound();
        $this->actingAs(userWithRole(Role::Guide))->get(route('guide.articles.show', $draft->slug))->assertNotFound();

        $editor = userWithRole(Role::Editor);
        $this->actingAs($editor)->get(route('guide.articles.show', $draft->slug))->assertOk()->assertSee('Preview: this article is a draft')->assertSee('noindex');
        $this->actingAs($editor)->get(route('guide.articles.show', $scheduled->slug))->assertOk()->assertSee('scheduled for');
        $this->actingAs($editor)->get(route('admin.articles.edit', $draft))->assertSee(route('guide.articles.show', $draft->slug))->assertSee('Preview');
    });
});

describe('gallery', function () {
    it('lists published albums that have photos, newest first', function () {
        $newer = Album::factory()->create(['title' => 'Qadisha Valley', 'taken_on' => '2026-10-10']);
        $newer->photos()->createMany([['path' => 'albums/a.jpg', 'position' => 1], ['path' => 'albums/b.jpg', 'position' => 2]]);
        Album::factory()->create(['title' => 'Horsh Ehden', 'taken_on' => '2026-09-01'])->photos()->create(['path' => 'albums/c.jpg']);
        Album::factory()->create(['title' => 'Empty album']);
        Album::factory()->create(['title' => 'Hidden album', 'is_published' => false])->photos()->create(['path' => 'albums/d.jpg']);

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertSeeInOrder(['Qadisha Valley', 'Horsh Ehden'])
            ->assertSee('2 photos')
            ->assertSee(Storage::disk('public')->url('albums/a.jpg'))
            ->assertDontSee('Empty album')
            ->assertDontSee('Hidden album');
    });

    it('shows an album with a lightbox and a link to its hike', function () {
        $event = Event::factory()->published()->past()->create(['title' => 'Qadisha Valley Descent']);
        $album = Album::factory()->create(['title' => 'Qadisha photos', 'event_id' => $event->id, 'description' => 'What a day.']);
        $album->photos()->createMany([['path' => 'albums/a.jpg', 'position' => 1], ['path' => 'albums/b.jpg', 'position' => 2]]);

        $this->get(route('gallery.show', $album->slug))
            ->assertOk()
            ->assertSee('What a day.')
            ->assertSee('Open photo 2 of 2')
            ->assertSee('aria-label="Photo viewer"', false)
            ->assertSee('Next photo')
            ->assertSee(route('events.show', $event));

        // The past hike links back to its photos.
        $this->get(route('events.show', $event))->assertSee('See the photos')->assertSee(route('gallery.show', $album->slug));
    });

    it('hides unpublished albums from visitors but lets gallery staff preview them', function () {
        $album = Album::factory()->create(['is_published' => false]);

        $this->get(route('gallery.show', $album->slug))->assertNotFound();
        $this->actingAs(userWithRole(Role::Editor))->get(route('gallery.show', $album->slug))->assertOk()->assertSee('Preview: this album is hidden');
    });

    it('does not link a past hike to a hidden or empty album', function () {
        $event = Event::factory()->published()->past()->create();
        Album::factory()->create(['event_id' => $event->id]);
        Album::factory()->create(['event_id' => $event->id, 'is_published' => false])->photos()->create(['path' => 'albums/x.jpg']);

        $this->get(route('events.show', $event))->assertOk()->assertDontSee('See the photos');
    });
});

describe('about', function () {
    it('shows the story, what we offer, the team and reviews', function () {
        TeamMember::factory()->create(['name' => 'Second Guide', 'role' => 'Guide', 'position' => 2]);
        TeamMember::factory()->create(['name' => 'Rami Khoury', 'role' => 'Founder & lead guide', 'bio' => 'Walked the whole LMT.', 'position' => 1, 'photo_path' => 'team/rami.jpg']);
        TeamMember::factory()->create(['name' => 'Hidden Person', 'is_active' => false]);

        $about = config('site.about_page');

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Since '.$about['founded'])
            ->assertSee($about['story'][0])
            ->assertSeeInOrder(['What We Offer', 'Challenging hikes', 'Expert-led', 'Hidden trails', 'Community', 'Seasonal adventures'])
            ->assertSeeInOrder(['Meet the Team', 'Rami Khoury', 'Founder &amp; lead guide', 'Walked the whole LMT.', 'Second Guide'], false)
            ->assertSee(Storage::disk('public')->url('team/rami.jpg'))
            ->assertDontSee('Hidden Person')
            ->assertSee($about['testimonials'][0][1])
            ->assertDontSee('Read our Google reviews')
            ->assertDontSee('This page is on its way');
    });

    it('shows real club numbers and the Google reviews link when set', function () {
        config()->set('site.about_page.google_reviews_url', 'https://maps.google.com/?cid=123');
        $past = Event::factory()->published()->past()->create();
        Event::factory()->published()->past()->create();
        EventRegistration::factory()->for($past)->create(['attendance' => Attendance::Attended]);

        $this->get(route('about'))
            ->assertSeeInOrder(['Hikes organised', '2', 'Hikers on the trail with us', '1'])
            ->assertSee('https://maps.google.com/?cid=123')
            ->assertSee('Read our Google reviews');
    });

    it('leaves out the team section when nobody is listed', function () {
        $this->get(route('about'))->assertOk()->assertDontSee('Meet the Team');
    });
});

describe('equipment guide', function () {
    it('shows a checklist per adventure with level badges and shop links', function () {
        Product::factory()->withStock(3)->create(['product_category_id' => ProductCategory::where('slug', 'footwear')->value('id')]);
        Product::factory()->withStock(3)->create(['product_category_id' => ProductCategory::where('slug', 'jacket')->value('id'), 'is_active' => false]);

        $this->get(route('guide.equipment'))
            ->assertOk()
            ->assertSee('What You Need for Each Adventure')
            ->assertSeeInOrder(['Hiking', 'Snowshoeing', 'Rafting', 'Camping'])
            ->assertSee('Hiking shoes or boots')
            ->assertSee('Level 3+')
            ->assertSee('x-show="level >= 4"', false)
            ->assertSee(DifficultyLevel::Xtreme->label())
            ->assertSee(route('shop.index', ['category' => 'footwear']))
            // Hidden products don't count: no link to an empty category.
            ->assertDontSee(route('shop.index', ['category' => 'jacket']))
            ->assertDontSee('This page is on its way');
    });

    it('lists each item of the config', function () {
        $response = $this->get(route('guide.equipment'));

        foreach (config('equipment.adventures') as $adventure) {
            foreach ($adventure['items'] as [$name]) {
                $response->assertSee($name);
            }
        }
    });
});

describe('contact', function () {
    it('shows the contact details, map link, WhatsApp and socials', function () {
        config()->set('site.contact', [...config('site.contact'), 'address' => 'Hamra, Beirut', 'phone' => '+961 70 123 456', 'whatsapp' => '96170123456', 'email' => 'club@example.com', 'maps_url' => null]);
        config()->set('site.social', ['facebook' => 'club.fb', 'instagram' => '', 'tiktok' => '', 'youtube' => '']);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Send us a message')
            ->assertSee('Hamra, Beirut')
            ->assertSee('https://www.google.com/maps/search/?api=1&amp;query=Hamra%2C%20Beirut', false)
            ->assertSee('tel:+96170123456', false)
            ->assertSee('https://wa.me/96170123456')
            ->assertSee('mailto:club@example.com', false)
            ->assertSee('https://www.facebook.com/club.fb');

        config()->set('site.contact.maps_url', 'https://maps.app.goo.gl/abc');
        $this->get(route('contact'))->assertSee('https://maps.app.goo.gl/abc');
    });

    it('emails the message to the club with the sender as reply-to', function () {
        Mail::fake();
        config()->set('site.contact.email', 'club@example.com');

        $this->post(route('contact.send'), [
            'name' => 'Rima Haddad', 'email' => 'Rima@Example.com', 'phone' => '+961 70 123 456', 'message' => 'Is the Saturday hike OK for beginners?',
        ])->assertRedirect(route('contact'))->assertSessionHas('status');

        Mail::assertQueued(ContactMessageMail::class, function (ContactMessageMail $mail) {
            $html = $mail->render();

            return $mail->hasTo('club@example.com')
                && $mail->hasReplyTo('rima@example.com', 'Rima Haddad')
                && $mail->data->phone === '+96170123456'
                && str_contains($html, 'Is the Saturday hike OK for beginners?')
                && str_contains($html, 'https://wa.me/96170123456');
        });

        $this->followingRedirects()->get(route('contact'))->assertOk();
    });

    it('validates the form and stops bots', function () {
        Mail::fake();

        $this->post(route('contact.send'), ['name' => '', 'email' => 'nope', 'phone' => 'abc', 'message' => 'Hi'])
            ->assertSessionHasErrors(['name', 'email', 'phone', 'message']);
        $this->post(route('contact.send'), ['name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Buy cheap stuff now!!', 'website' => 'http://spam'])
            ->assertSessionHasErrors('website');

        Mail::assertNothingQueued();
    });

    it('limits how often a visitor can send', function () {
        Mail::fake();
        $form = ['name' => 'Rima', 'email' => 'rima@example.com', 'message' => 'A question about hikes.'];

        foreach (range(1, 5) as $i) {
            $this->post(route('contact.send'), $form)->assertRedirect();
        }
        $this->post(route('contact.send'), $form)->assertTooManyRequests();
    });
});

it('adds the new pages to the menu and the sitemap', function () {
    $article = Article::factory()->create();
    Article::factory()->draft()->create(['slug' => 'secret-draft']);
    $album = Album::factory()->create();
    $album->photos()->create(['path' => 'albums/a.jpg']);

    $this->get(route('home'))->assertSee(route('gallery.index'))->assertSee(route('guide.articles'))->assertSee(route('about'))->assertSee(route('guide.equipment'))->assertSee(route('contact'));

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('about'))
        ->assertSee(route('contact'))
        ->assertSee(route('guide.equipment'))
        ->assertSee(route('guide.articles'))
        ->assertSee(route('guide.articles.show', $article->slug))
        ->assertSee(route('gallery.index'))
        ->assertSee(route('gallery.show', $album->slug))
        ->assertDontSee('secret-draft');
});
