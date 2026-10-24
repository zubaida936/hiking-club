<?php

use App\Models\Event;
use App\Models\HeroSlide;
use Illuminate\Support\Facades\Storage;

it('shows the homepage sections in order', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder([
            config('site.hero')[0]['heading'],
            'About '.config('app.name'),
            'Next Challenge',
            'Top 10 Hikers',
            'X Events',
            'Never miss a hike',
            'Get in touch',
        ]);
});

it('shows the placeholder hero slides while the CMS has none', function () {
    HeroSlide::factory()->create(['heading' => 'Hidden slide', 'is_active' => false]);

    $response = $this->get('/')->assertOk()->assertDontSee('Hidden slide');

    foreach (config('site.hero') as $slide) {
        $response->assertSee($slide['heading'])->assertSee($slide['cta'])->assertSee(route($slide['route']));
    }

    $response->assertSee('aria-label="Next slide"', false);
});

it('shows the active CMS hero slides in order instead of the placeholders', function () {
    HeroSlide::factory()->create(['heading' => 'Second slide', 'position' => 2, 'cta_label' => 'Visit the shop', 'cta_url' => '/shop']);
    HeroSlide::factory()->create(['heading' => 'First slide', 'position' => 1, 'eyebrow' => '❄️ Winter', 'image_path' => 'hero/first.jpg']);
    HeroSlide::factory()->create(['heading' => 'No button', 'position' => 3, 'cta_label' => null, 'cta_url' => null]);
    HeroSlide::factory()->create(['heading' => 'Hidden slide', 'is_active' => false]);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['❄️ Winter', 'First slide', 'Second slide', 'Visit the shop', 'No button'])
        ->assertSee(url('/shop'))
        ->assertSee(Storage::disk('public')->url('hero/first.jpg'))
        ->assertDontSee('Hidden slide')
        ->assertDontSee(config('site.hero')[0]['heading']);
});

it('features the nearest published upcoming event as the next challenge', function () {
    $next = Event::factory()->published()->create([
        'title' => 'Qadisha Valley Descent',
        'starts_on' => now()->addDays(5)->toDateString(),
        'season_tag' => '🍂 Autumn Trail',
        'fee_cents' => 3000,
        'distance_km' => 12.5,
        'elevation_gain_m' => 640,
    ]);
    Event::factory()->published()->create(['title' => 'Later Hike', 'starts_on' => now()->addDays(20)->toDateString()]);
    Event::factory()->create(['title' => 'Draft Hike', 'starts_on' => now()->addDays(2)->toDateString()]);
    Event::factory()->published()->cancelled()->create(['title' => 'Cancelled Hike', 'starts_on' => now()->addDays(1)->toDateString()]);
    Event::factory()->published()->past()->create(['title' => 'Past Hike']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Qadisha Valley Descent')
        ->assertSee($next->starts_on->format('l, j F Y'))
        ->assertSee('🍂 Autumn Trail')
        ->assertSee($next->difficulty_level->label())
        ->assertSee('12.5 km')
        ->assertSee('+640 m')
        ->assertSee('$30')
        ->assertDontSee('Later Hike')
        ->assertDontSee('Draft Hike')
        ->assertDontSee('Cancelled Hike')
        ->assertDontSee('Past Hike');
});

it('shows an event happening today as the next challenge', function () {
    Event::factory()->published()->create(['title' => 'Sunrise Hike', 'starts_on' => today()->toDateString()]);

    $this->get('/')->assertSee('Sunrise Hike');
});

it('shows a friendly message when no event is scheduled', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Our next adventure is being planned');
});

it('shows an empty leaderboard with progress links', function () {
    $this->get('/')
        ->assertSee('The leaderboard fills up as hikers complete hikes and earn points.')
        ->assertSee('href="'.route('leaderboard').'"', false)
        ->assertSee('View My Progress')
        ->assertSee('href="'.route('account').'"', false)
        ->assertSee('href="'.route('rank-system').'"', false);
});

it('has a working newsletter form', function () {
    $this->get('/')
        ->assertSee('action="'.route('newsletter.subscribe').'"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="phone"', false)
        ->assertDontSee('Sign-up opens soon.');
});

it('shows the contact details from config', function () {
    config()->set('site.contact.phone', '+961 70 123 456');
    config()->set('site.contact.whatsapp', '96170123456');
    config()->set('site.contact.email', 'club@example.com');
    config()->set('site.social.instagram', 'our.club');

    $this->get('/')
        ->assertSee('+961 70 123 456')
        ->assertSee('href="https://wa.me/96170123456?text=', false)
        ->assertSee('href="mailto:club@example.com"', false)
        ->assertSee('@our.club');
});
