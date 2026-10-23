<?php

use App\Models\Event;

beforeEach(fn () => config(['site.contact.whatsapp' => '+961 70 123 456']));

it('shows everything about an upcoming event', function () {
    $event = Event::factory()->published()->withMeetingPoints(2)->create([
        'title' => 'Qadisha Valley Descent',
        'location' => 'Bcharre',
        'season_tag' => '🍂 Autumn Trail',
        'summary' => 'Down into the holy valley.',
        'description' => "First paragraph.\n\nSecond paragraph.",
        'distance_km' => 14.5,
        'elevation_gain_m' => 450,
        'elevation_loss_m' => 1100,
        'duration_hours' => 6.5,
        'fee_cents' => 3500,
        'capacity' => 25,
        'included' => ['Transportation', 'Guiding'],
        'what_to_bring' => ['2L water'],
        'itinerary' => ['07:30 Departure', 'Lunch by the river'],
        'cover_image_path' => 'events/cover.jpg',
        'photo_credit' => 'Jane Hiker',
    ]);

    $this->get("/events/{$event->slug}")
        ->assertOk()
        ->assertSee('<title>Qadisha Valley Descent · '.e(config('app.name')).'</title>', false)
        ->assertSee('Back to events')
        ->assertSee(route('events.index'))
        ->assertSee($event->starts_on->format('l, j F Y'))
        ->assertSee('Bcharre')
        ->assertSee('🍂 Autumn Trail')
        ->assertSee($event->difficulty_level->label())
        ->assertSee($event->difficulty_level->description())
        ->assertSee((string) $event->effort_score)
        ->assertSee('14.5 km')
        ->assertSee('+450 / -1100 m')
        ->assertSee('6.5 h')
        ->assertSee('$35')
        ->assertSee('25 of 25')
        ->assertSeeInOrder(['First paragraph.', 'Second paragraph.'])
        ->assertSeeInOrder(['Itinerary', '07:30', 'Departure', 'Lunch by the river'])
        ->assertSee($event->meetingPoints->first()->name)
        ->assertSeeInOrder(["What's included", 'Transportation', 'Guiding', 'What to bring', '2L water'], false)
        ->assertSee('Technical details')
        ->assertSee('Photo: Jane Hiker')
        ->assertSee('og:image" content="'.$event->coverImageUrl(), false)
        ->assertSee('https://wa.me/96170123456?text='.rawurlencode('Hi! I have a question about "Qadisha Valley Descent"'), false)
        ->assertSee('Ask on WhatsApp');
});

it('is linked from the event cards and the homepage', function () {
    $event = Event::factory()->published()->create();

    $this->get(route('events.index'))->assertSee(route('events.show', $event))->assertSee('View Details');
    $this->get(route('home'))->assertSee(route('events.show', $event));
    expect(route('events.show', $event))->toEndWith("/events/{$event->slug}");
});

it('hides drafts and deleted events', function () {
    $draft = Event::factory()->create();
    $deleted = Event::factory()->published()->create();
    $deleted->delete();

    $this->get(route('events.show', $draft))->assertNotFound();
    $this->get("/events/{$deleted->slug}")->assertNotFound();
    $this->get('/events/no-such-hike')->assertNotFound();
});

it('explains a cancellation instead of returning a 404', function () {
    $event = Event::factory()->published()->cancelled()->create();

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('This hike has been cancelled.')
        ->assertSee('Bad weather forecast')
        ->assertDontSee('Ask on WhatsApp')
        ->assertDontSee('Seats left')
        ->assertSee('EventCancelled');
});

it('marks past events and drops the inquiry button', function () {
    $event = Event::factory()->published()->past()->create();

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('This hike has already taken place.')
        ->assertDontSee('Ask on WhatsApp')
        ->assertSee('See upcoming hikes');
});

it('leaves out optional sections the organizer did not fill', function () {
    config(['site.contact.whatsapp' => '']);
    $event = Event::factory()->published()->create([
        'summary' => null, 'description' => null, 'itinerary' => null, 'included' => null,
        'what_to_bring' => null, 'duration_hours' => null, 'photo_credit' => 'Nobody',
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertDontSee('About this hike')
        ->assertDontSee('Itinerary')
        ->assertDontSee("What's included", false)
        ->assertDontSee('Route map')
        ->assertDontSee('Photo: Nobody') // no cover image to credit
        ->assertDontSee('Ask on WhatsApp')
        ->assertDontSee('Estimated duration');
});

it('draws the route map and offers the GPX download', function () {
    $event = Event::factory()->published()->create([
        'gpx_path' => 'events/gpx/abc.gpx',
        'route_points' => [[34.29, 35.95], [34.3, 35.97]],
    ]);

    $this->get(route('events.show', $event))
        ->assertSee('Route map')
        ->assertSee('routeMap(')
        ->assertSee('34.29')
        ->assertSee('download="'.$event->slug.'.gpx"', false);
});

it('adds schema.org event data for search engines', function () {
    $event = Event::factory()->published()->create(['title' => 'Cedars </script> Loop', 'fee_cents' => 3000]);

    $html = $this->get(route('events.show', $event))->assertOk()->getContent();

    preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $m);
    $data = json_decode($m[1], true);

    expect($data)->toMatchArray(['@type' => 'Event', 'name' => 'Cedars </script> Loop', 'startDate' => $event->starts_on->toDateString()])
        ->and($data['offers'])->toMatchArray(['price' => '30.00', 'priceCurrency' => 'USD']);
});

it('lists event pages in the sitemap', function () {
    $event = Event::factory()->published()->create();
    $draft = Event::factory()->create();

    $this->get(route('sitemap'))
        ->assertSee(route('events.show', $event))
        ->assertDontSee(route('events.show', $draft));
});
