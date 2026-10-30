<?php

use App\Models\Album;
use App\Models\Event;
use App\Models\EventRegistration;

describe('upcoming hikes', function () {
    it('lists published upcoming hikes, soonest first, with seats and registration state', function () {
        $later = Event::factory()->published()->create(['title' => 'Later', 'starts_on' => now()->addDays(20)->toDateString(), 'capacity' => 2]);
        $soon = Event::factory()->published()->create(['title' => 'Soon', 'starts_on' => now()->addDays(5)->toDateString(), 'fee_cents' => 3500]);
        Event::factory()->create(['title' => 'Draft', 'starts_on' => now()->addDays(3)->toDateString()]);
        Event::factory()->published()->past()->create(['title' => 'Past']);
        EventRegistration::factory()->count(2)->for($later)->create();

        $this->getJson(route('api.v1.events.index'))
            ->assertOk()
            ->assertJsonPath('data.*.title', ['Soon', 'Later'])
            ->assertJsonPath('data.0.slug', $soon->slug)
            ->assertJsonPath('data.0.starts_on', $soon->starts_on->toDateString())
            ->assertJsonPath('data.0.fee', ['cents' => 3500, 'formatted' => '$35'])
            ->assertJsonPath('data.0.difficulty.level', $soon->difficulty_level->value)
            ->assertJsonPath('data.0.difficulty.label', $soon->difficulty_level->label())
            ->assertJsonPath('data.0.registration.open', true)
            ->assertJsonPath('data.0.web_url', route('events.show', $soon))
            ->assertJsonPath('data.1.seats', ['capacity' => 2, 'left' => 0, 'sold_out' => true])
            ->assertJsonPath('meta.total', 2);
    });

    it('filters by level, month and X events', function () {
        $foundation = Event::factory()->published()->create(['starts_on' => now()->addDays(3)->toDateString(), 'distance_km' => 6, 'elevation_gain_m' => 150, 'elevation_loss_m' => 150]);
        $xtreme = Event::factory()->published()->create(['starts_on' => now()->addMonths(2)->toDateString(), 'is_xtreme' => true]);

        $this->getJson(route('api.v1.events.index', ['level' => $foundation->difficulty_level->value]))
            ->assertJsonPath('data.*.id', fn (array $ids) => in_array($foundation->id, $ids));
        $this->getJson(route('api.v1.events.index', ['month' => $xtreme->starts_on->format('Y-m')]))
            ->assertJsonPath('data.*.id', [$xtreme->id]);
        $this->getJson(route('api.v1.events.index', ['xtreme' => 1]))->assertJsonPath('data.*.id', [$xtreme->id]);
        $this->getJson(route('api.v1.events.index', ['xtreme' => 0]))->assertJsonPath('data.*.id', [$foundation->id]);
    });

    it('searches the title and the place', function () {
        $ehden = Event::factory()->published()->create(['title' => 'Horch Ehden Nature Reserve', 'location' => 'Ehden', 'starts_on' => now()->addDays(3)->toDateString()]);
        $chouf = Event::factory()->published()->create(['title' => 'Cedar Ridge', 'location' => 'Barouk', 'starts_on' => now()->addDays(5)->toDateString()]);

        $this->getJson(route('api.v1.events.index', ['search' => 'horch']))->assertJsonPath('data.*.id', [$ehden->id]);
        $this->getJson(route('api.v1.events.index', ['search' => 'barouk']))->assertJsonPath('data.*.id', [$chouf->id]);
        $this->getJson(route('api.v1.events.index', ['search' => '  ']))->assertJsonCount(2, 'data');
        $this->getJson(route('api.v1.events.index', ['search' => str_repeat('a', 101)]))->assertJsonValidationErrors('search');
    });

    it('rejects unknown filter values', function () {
        $this->getJson(route('api.v1.events.index', ['level' => 9, 'month' => 'November']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['level', 'month']);
    });

    it('needs no login', function () {
        Event::factory()->published()->create(['starts_on' => now()->addDays(3)->toDateString()]);

        $this->getJson(route('api.v1.events.index'))->assertOk()->assertJsonCount(1, 'data');
    });
});

describe('past hikes', function () {
    it('lists published past hikes, newest first, by month', function () {
        $older = Event::factory()->published()->create(['title' => 'Older', 'starts_on' => now()->subMonths(3)->toDateString()]);
        Event::factory()->published()->create(['title' => 'Newer', 'starts_on' => now()->subDays(3)->toDateString()]);
        Event::factory()->published()->create(['title' => 'Upcoming', 'starts_on' => now()->addDays(3)->toDateString()]);

        $this->getJson(route('api.v1.events.past'))->assertOk()->assertJsonPath('data.*.title', ['Newer', 'Older']);
        $this->getJson(route('api.v1.events.past', ['month' => $older->starts_on->format('Y-m')]))->assertJsonPath('data.*.title', ['Older']);
    });

    it('links a past hike to its published album with photos', function () {
        $withPhotos = Event::factory()->published()->create(['starts_on' => now()->subDays(3)->toDateString()]);
        $album = Album::factory()->create(['event_id' => $withPhotos->id]);
        $album->photos()->create(['path' => 'albums/a.jpg']);
        $emptyAlbum = Event::factory()->published()->create(['starts_on' => now()->subDays(5)->toDateString()]);
        Album::factory()->create(['event_id' => $emptyAlbum->id]);
        $hiddenAlbum = Event::factory()->published()->create(['starts_on' => now()->subDays(7)->toDateString()]);
        Album::factory()->create(['event_id' => $hiddenAlbum->id, 'is_published' => false])->photos()->create(['path' => 'albums/b.jpg']);

        $this->getJson(route('api.v1.events.past'))
            ->assertOk()
            ->assertJsonPath('data.*.album_slug', [$album->slug, null, null])
            // The list fields are all still there.
            ->assertJsonPath('data.0.slug', $withPhotos->slug);
    });

    it('pages by 20', function () {
        Event::factory()->published()->count(21)->sequence(fn ($s) => ['title' => "Hike {$s->index}", 'starts_on' => now()->subDays($s->index + 1)->toDateString()])->create();

        $this->getJson(route('api.v1.events.past'))->assertJsonCount(20, 'data')->assertJsonPath('meta.last_page', 2);
        $this->getJson(route('api.v1.events.past', ['page' => 2]))->assertJsonCount(1, 'data');
    });

    it('lists the months that have hikes', function () {
        $past = Event::factory()->published()->create(['starts_on' => now()->subMonths(2)->startOfMonth()->addDays(4)->toDateString()]);
        Event::factory()->published()->count(2)->create(['starts_on' => now()->addMonths(2)->startOfMonth()->addDays(4)->toDateString()]);
        Event::factory()->create(['starts_on' => now()->addMonths(4)->toDateString()]);

        $this->getJson(route('api.v1.events.months'))->assertOk()->assertExactJson(['data' => [
            ['month' => now()->addMonths(2)->format('Y-m'), 'label' => now()->addMonths(2)->format('F Y'), 'upcoming' => 2, 'past' => 0],
            ['month' => $past->starts_on->format('Y-m'), 'label' => $past->starts_on->format('F Y'), 'upcoming' => 0, 'past' => 1],
        ]]);
    });
});

describe('hike detail', function () {
    it('shows everything about a hike', function () {
        $event = Event::factory()->published()->withMeetingPoints(2)->create([
            'starts_on' => now()->addDays(10)->toDateString(),
            'description' => "First paragraph.\n\nSecond paragraph.",
            'included' => ['Transportation', 'Guiding'],
            'what_to_bring' => ['2L water'],
            'itinerary' => ['07:30 Departure', 'Lunch by the river'],
            'cover_image_path' => 'events/cover.jpg',
            'photo_credit' => 'Jane Hiker',
        ]);
        $event->updates()->create(['message' => 'Bring a rain jacket.', 'emailed_count' => 0]);
        $point = $event->meetingPoints()->first();

        $this->getJson(route('api.v1.events.show', $event))
            ->assertOk()
            ->assertJsonPath('data.title', $event->title)
            ->assertJsonPath('data.description', "First paragraph.\n\nSecond paragraph.")
            ->assertJsonPath('data.included', ['Transportation', 'Guiding'])
            ->assertJsonPath('data.what_to_bring', ['2L water'])
            ->assertJsonPath('data.itinerary', [['time' => '07:30', 'text' => 'Departure'], ['time' => null, 'text' => 'Lunch by the river']])
            ->assertJsonPath('data.meeting_points.0', ['name' => $point->name, 'pickup_time' => $point->pickupTimeShort()])
            ->assertJsonCount(2, 'data.meeting_points')
            ->assertJsonPath('data.updates.0.message', 'Bring a rain jacket.')
            ->assertJsonPath('data.cover_image_url', fn (string $url) => str_ends_with($url, 'events/cover.jpg'))
            ->assertJsonPath('data.photo_credit', 'Jane Hiker')
            ->assertJsonPath('data.album_slug', null);
    });

    it('explains a cancelled hike and links a past hike to its photos', function () {
        $cancelled = Event::factory()->published()->cancelled()->create(['starts_on' => now()->addDays(5)->toDateString()]);
        $past = Event::factory()->published()->past()->create();
        $album = Album::factory()->create(['event_id' => $past->id]);
        $album->photos()->create(['path' => 'albums/a.jpg']);

        $this->getJson(route('api.v1.events.show', $cancelled))
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Bad weather forecast')
            ->assertJsonPath('data.registration.open', false);

        $this->getJson(route('api.v1.events.show', $past))->assertJsonPath('data.album_slug', $album->slug);
    });

    it('hides drafts', function () {
        $draft = Event::factory()->create();

        $this->getJson(route('api.v1.events.show', $draft))->assertNotFound()->assertJsonStructure(['message']);
        $this->getJson('/api/v1/events/no-such-hike')->assertNotFound();
    });
});
