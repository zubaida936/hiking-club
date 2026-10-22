<?php

use App\Models\Event;

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

it('shows every hero slide with its call to action', function () {
    $response = $this->get('/')->assertOk();

    foreach (config('site.hero') as $slide) {
        $response->assertSee($slide['heading'])->assertSee($slide['cta']);
    }

    $response->assertSee('aria-label="Next slide"', false);
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

it('shows the leaderboard placeholder with progress links', function () {
    $this->get('/')
        ->assertSee('The leaderboard goes live once members start earning points')
        ->assertSee('View My Progress')
        ->assertSee('href="'.route('account').'"', false)
        ->assertSee('href="'.route('rank-system').'"', false);
});

it('keeps the newsletter form disabled until sign-up exists', function () {
    $this->get('/')
        ->assertSee('Sign-up opens soon.')
        ->assertSee('id="newsletter-email" type="email" placeholder="you@example.com" disabled', false);
});

it('shows the contact details from config', function () {
    config()->set('site.contact', [
        'phone' => '+961 70 123 456',
        'whatsapp' => '96170123456',
        'email' => 'club@example.com',
        'instagram' => 'our.club',
        'facebook' => 'ourclub',
    ]);

    $this->get('/')
        ->assertSee('+961 70 123 456')
        ->assertSee('href="https://wa.me/96170123456"', false)
        ->assertSee('href="mailto:club@example.com"', false)
        ->assertSee('@our.club');
});
