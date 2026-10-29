<?php

use App\Domain\Loyalty\Enums\PointType;
use App\Mail\ConfirmSubscriptionMail;
use App\Mail\ContactMessageMail;
use App\Models\Album;
use App\Models\Article;
use App\Models\Event;
use App\Models\HeroSlide;
use App\Models\Hiker;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Subscriber;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

it('gives the home screen: slides, next hike and top hikers', function () {
    HeroSlide::factory()->create(['heading' => 'Into the cedars', 'is_active' => true]);
    $next = Event::factory()->published()->create(['starts_on' => now()->addDays(3)->toDateString()]);
    Event::factory()->published()->create(['starts_on' => now()->addDays(9)->toDateString()]);
    Hiker::factory()->create(['name' => 'Rima Haddad'])->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 40, 'description' => 'Test']);

    $this->getJson(route('api.v1.home'))
        ->assertOk()
        ->assertJsonPath('data.slides.0.heading', 'Into the cedars')
        ->assertJsonPath('data.next_event.slug', $next->slug)
        ->assertJsonPath('data.top_hikers.0', ['position' => 1, 'name' => 'Rima H.', 'rank' => ['key' => 'pathfinder', 'label' => 'Pathfinder'], 'points' => 40]);
});

it('falls back to the placeholder slides', function () {
    $this->getJson(route('api.v1.home'))
        ->assertJsonCount(count(config('site.hero')), 'data.slides')
        ->assertJsonPath('data.next_event', null);
});

it('gives the about page with the team', function () {
    TeamMember::factory()->create(['name' => 'Karim Saad', 'role' => 'Lead guide', 'is_active' => true]);
    TeamMember::factory()->create(['is_active' => false]);

    $this->getJson(route('api.v1.about'))
        ->assertOk()
        ->assertJsonPath('data.founded', config('site.about_page.founded'))
        ->assertJsonPath('data.offers.0.title', config('site.about_page.offers.0.1'))
        ->assertJsonPath('data.testimonials.0.name', config('site.about_page.testimonials.0.0'))
        ->assertJsonPath('data.team.*.name', ['Karim Saad'])
        ->assertJsonPath('data.team.0.role', 'Lead guide')
        ->assertJsonStructure(['data' => ['stats' => ['hikes', 'hikers']]]);
});

it('gives the equipment lists with shop links only for stocked categories', function () {
    $footwear = ProductCategory::firstOrCreate(['slug' => 'footwear'], ['name' => 'Footwear']);
    Product::factory()->withStock()->create(['product_category_id' => $footwear->id]);
    ProductCategory::query()->where('slug', '!=', 'footwear')->each(fn (ProductCategory $c) => $c->products()->update(['is_active' => false]));

    $hiking = collect($this->getJson(route('api.v1.equipment'))->assertOk()->json('data.adventures'))->firstWhere('key', 'hiking');

    expect($hiking['label'])->toBe('Hiking')
        ->and($hiking['items'][0])->toMatchArray(['name' => 'Hiking shoes or boots', 'from_level' => null, 'shop_category' => 'footwear'])
        ->and($hiking['items'][1]['shop_category'])->toBeNull()
        ->and(collect($hiking['items'])->firstWhere('name', 'Headlamp')['from_level'])->toBe(3);
});

describe('guides', function () {
    it('lists published articles and shows one as HTML', function () {
        $article = Article::factory()->create(['title' => 'How much water to carry', 'body' => "# Water\n\nCarry **two litres**.\n\n<script>alert(1)</script>", 'published_at' => now()->subDay()]);
        Article::factory()->create(['published_at' => now()->subDays(3)]);
        $draft = Article::factory()->draft()->create();
        $scheduled = Article::factory()->scheduled()->create();

        $this->getJson(route('api.v1.guides.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'How much water to carry');

        $html = $this->getJson(route('api.v1.guides.show', $article))
            ->assertOk()
            ->assertJsonPath('data.web_url', route('guide.articles.show', $article))
            ->assertJsonCount(1, 'data.more')
            ->json('data.body_html');
        expect($html)->toContain('<strong>two litres</strong>')->not->toContain('<script>');

        $this->getJson(route('api.v1.guides.show', $draft))->assertNotFound();
        $this->getJson(route('api.v1.guides.show', $scheduled))->assertNotFound();
    });
});

describe('gallery', function () {
    it('lists published albums with photos and shows one', function () {
        $hike = Event::factory()->published()->past()->create();
        $album = Album::factory()->create(['title' => 'Horsh Ehden', 'event_id' => $hike->id, 'taken_on' => now()->subDays(2)->toDateString()]);
        $album->photos()->createMany([['path' => 'albums/a.jpg', 'position' => 1], ['path' => 'albums/b.jpg', 'position' => 2]]);
        Album::factory()->create(); // no photos
        $hidden = Album::factory()->create(['is_published' => false]);
        $hidden->photos()->create(['path' => 'albums/c.jpg']);

        $this->getJson(route('api.v1.gallery.index'))
            ->assertOk()
            ->assertJsonPath('data.*.slug', [$album->slug])
            ->assertJsonPath('data.0.photos_count', 2)
            ->assertJsonPath('data.0.cover_url', fn (string $url) => str_ends_with($url, 'albums/a.jpg'));

        $this->getJson(route('api.v1.gallery.show', $album))
            ->assertOk()
            ->assertJsonCount(2, 'data.photos')
            ->assertJsonPath('data.event_slug', $hike->slug);

        $this->getJson(route('api.v1.gallery.show', $hidden))->assertNotFound();
    });
});

describe('contact', function () {
    it('gives the contact details', function () {
        config(['site.contact.whatsapp' => '96170123456', 'site.contact.maps_url' => null, 'site.contact.address' => 'Beirut, Lebanon']);

        $this->getJson(route('api.v1.contact'))
            ->assertOk()
            ->assertJsonPath('data.whatsapp_url', fn (string $url) => str_starts_with($url, 'https://wa.me/96170123456'))
            ->assertJsonPath('data.maps_url', 'https://www.google.com/maps/search/?api=1&query=Beirut%2C%20Lebanon')
            ->assertJsonPath('data.social.0.network', 'facebook');
    });

    it('sends a message to the club', function () {
        $this->postJson(route('api.v1.contact.send'), ['name' => 'Rima', 'email' => 'rima@example.com', 'message' => 'Do you rent snowshoes?'])
            ->assertAccepted()
            ->assertJsonPath('message', 'Thank you! Your message is on its way. We usually answer within a day.');

        Mail::assertQueued(ContactMessageMail::class);

        $this->postJson(route('api.v1.contact.send'), ['name' => 'Rima', 'email' => 'nope', 'message' => 'Hi'])
            ->assertJsonValidationErrors(['email', 'message' => 'Please write a little more so we can help.']);
    });
});

describe('newsletter', function () {
    it('subscribes with an email (confirmed by link) or a phone', function () {
        $this->postJson(route('api.v1.newsletter.subscribe'), ['email' => 'hiker@example.com'])
            ->assertAccepted()
            ->assertJsonPath('message', 'Almost there! Check your inbox and click the link to confirm your email.');
        Mail::assertQueued(ConfirmSubscriptionMail::class);

        $this->postJson(route('api.v1.newsletter.subscribe'), ['phone' => '+961 71 222 333'])
            ->assertAccepted()
            ->assertJsonPath('message', "Thanks! You're on the list for Next Destination Alerts.");

        expect(Subscriber::count())->toBe(2);

        $this->postJson(route('api.v1.newsletter.subscribe'), [])->assertJsonValidationErrors(['email' => 'Enter your email or your phone number.']);
    });
});
