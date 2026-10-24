<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Events\Enums\EventStatus;
use App\Domain\Events\Events\EventCancelled;
use App\Domain\Identity\Enums\Role;
use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event as EventBus;
use Illuminate\Support\Facades\Storage;

function eventPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Horch Ehden Nature Reserve',
        'slug' => '',
        'season_tag' => '🍂 Autumn Trail',
        'location' => 'Ehden',
        'starts_on' => now()->addDays(10)->format('Y-m-d'),
        'summary' => 'Autumn colours in the reserve.',
        'description' => 'Point-to-point route.',
        'distance_km' => '8',
        'elevation_gain_m' => '220',
        'elevation_loss_m' => '320',
        'duration_hours' => '4.5',
        'fee' => '30',
        'capacity' => '30',
        'included' => "Transportation\nGuiding\n\nGuiding\n",
        'what_to_bring' => "Water\nSnacks",
        'meeting_points' => [
            ['name' => 'Sour', 'pickup_time' => '05:50'],
            ['name' => 'Beirut - Jnah', 'pickup_time' => '07:30'],
            ['name' => '', 'pickup_time' => ''], // empty repeater row, ignored
        ],
    ], $overrides);
}

beforeEach(function () {
    Storage::fake('public');
    $this->admin = userWithRole(Role::Admin);
});

describe('listing', function () {
    it('lists events with filters', function () {
        Event::factory()->published()->create(['title' => 'Upcoming Published']);
        Event::factory()->past()->create(['title' => 'Old Draft']);

        $this->actingAs($this->admin)->get(route('admin.events.index'))
            ->assertOk()->assertSee('Upcoming Published')->assertSee('Old Draft');

        $this->actingAs($this->admin)->get(route('admin.events.index', ['when' => 'upcoming']))
            ->assertSee('Upcoming Published')->assertDontSee('Old Draft');

        $this->actingAs($this->admin)->get(route('admin.events.index', ['status' => 'draft']))
            ->assertSee('Old Draft')->assertDontSee('Upcoming Published');

        $this->actingAs($this->admin)->get(route('admin.events.index', ['q' => 'Upcoming']))
            ->assertSee('Upcoming Published')->assertDontSee('Old Draft');
    });

    it('filters by difficulty level', function () {
        Event::factory()->create(['title' => 'Easy One', 'difficulty_level' => DifficultyLevel::Foundation]);
        Event::factory()->create(['title' => 'Hard One', 'difficulty_level' => DifficultyLevel::Xtreme]);

        $this->actingAs($this->admin)->get(route('admin.events.index', ['level' => 4]))
            ->assertSee('Hard One')->assertDontSee('Easy One');
    });
});

describe('create & update', function () {
    it('creates a draft event with computed difficulty, slug, meeting points and lists', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.events.store'), eventPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $event = Event::firstOrFail();

        expect($event->status)->toBe(EventStatus::Draft)
            ->and($event->slug)->toBe('horch-ehden-nature-reserve')
            ->and($event->effort_score)->toBe(90)
            ->and($event->difficulty_level)->toBe(DifficultyLevel::Foundation)
            ->and($event->fee_cents)->toBe(3000)
            ->and($event->included)->toBe(['Transportation', 'Guiding'])
            ->and($event->created_by)->toBe($this->admin->id)
            ->and($event->meetingPoints->pluck('name')->all())->toBe(['Sour', 'Beirut - Jnah'])
            ->and($event->meetingPoints->first()->pickupTimeShort())->toBe('05:50');
    });

    it('makes duplicate slugs unique', function () {
        Event::factory()->create(['slug' => 'horch-ehden-nature-reserve']);

        $this->actingAs($this->admin)->post(route('admin.events.store'), eventPayload());

        expect(Event::latest('id')->first()->slug)->toBe('horch-ehden-nature-reserve-2');
    });

    it('never gives an event the URL of the month archive', function () {
        $this->actingAs($this->admin)->post(route('admin.events.store'), eventPayload(['title' => 'Archive', 'slug' => 'archive']))
            ->assertSessionHasErrors('slug');

        $this->actingAs($this->admin)->post(route('admin.events.store'), eventPayload(['title' => 'Archive']));
        $event = Event::where('title', 'Archive')->sole();
        expect($event->slug)->toBe('archive-2');

        $this->actingAs($this->admin)->post(route('admin.events.duplicate', $event));
        expect(Event::pluck('slug')->all())->not->toContain('archive');
    });

    it('validates input', function (array $overrides, string $field) {
        $this->actingAs($this->admin)
            ->post(route('admin.events.store'), eventPayload($overrides))
            ->assertSessionHasErrors($field);
    })->with([
        'missing title' => [['title' => ''], 'title'],
        'missing location' => [['location' => ''], 'location'],
        'bad date' => [['starts_on' => '31/12/2026'], 'starts_on'],
        'zero distance' => [['distance_km' => '0'], 'distance_km'],
        'negative gain' => [['elevation_gain_m' => '-5'], 'elevation_gain_m'],
        'negative fee' => [['fee' => '-1'], 'fee'],
        'fee with 3 decimals' => [['fee' => '10.555'], 'fee'],
        'zero capacity' => [['capacity' => '0'], 'capacity'],
        'invalid slug' => [['slug' => 'Not a slug!'], 'slug'],
        'pickup without name' => [['meeting_points' => [['name' => '', 'pickup_time' => '06:00']]], 'meeting_points.0.name'],
        'bad pickup time' => [['meeting_points' => [['name' => 'Sour', 'pickup_time' => '25:99']]], 'meeting_points.0.pickup_time'],
    ]);

    it('updates an event, recomputes difficulty and replaces meeting points', function () {
        $event = Event::factory()->withMeetingPoints(3)->create();

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), eventPayload([
                'slug' => $event->slug,
                'distance_km' => '30',
                'elevation_gain_m' => '1500',
                'elevation_loss_m' => '1500',
                'meeting_points' => [['name' => 'Saida', 'pickup_time' => '06:40']],
            ]))
            ->assertRedirect(route('admin.events.edit', $event));

        $event->refresh();
        expect($event->difficulty_level)->toBe(DifficultyLevel::Xtreme)
            ->and($event->meetingPoints)->toHaveCount(1)
            ->and($event->updated_by)->toBe($this->admin->id);
    });

    it('keeps the slug when the title changes and slug is left empty', function () {
        $event = Event::factory()->create(['slug' => 'original-slug']);

        $this->actingAs($this->admin)->put(route('admin.events.update', $event), eventPayload(['title' => 'Renamed']));

        expect($event->fresh()->slug)->toBe('original-slug');
    });
});

describe('cover image', function () {
    it('uploads, replaces and removes the cover image', function () {
        $this->actingAs($this->admin)->post(route('admin.events.store'), eventPayload([
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 600),
        ]));
        $event = Event::firstOrFail();
        $first = $event->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($this->admin)->put(route('admin.events.update', $event), eventPayload([
            'slug' => $event->slug,
            'cover_image' => UploadedFile::fake()->image('new.jpg', 1200, 600),
        ]));
        $second = $event->fresh()->cover_image_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->actingAs($this->admin)->put(route('admin.events.update', $event), eventPayload([
            'slug' => $event->slug,
            'remove_cover_image' => '1',
        ]));
        expect($event->fresh()->cover_image_path)->toBeNull();
        Storage::disk('public')->assertMissing($second);
    });

    it('rejects small or non-image files', function (UploadedFile $file) {
        $this->actingAs($this->admin)
            ->post(route('admin.events.store'), eventPayload(['cover_image' => $file]))
            ->assertSessionHasErrors('cover_image');
    })->with([
        'too small' => fn () => UploadedFile::fake()->image('small.jpg', 300, 200),
        'pdf' => fn () => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        'too big' => fn () => UploadedFile::fake()->image('big.jpg', 1200, 600)->size(6000),
    ]);
});

describe('lifecycle', function () {
    it('publishes an upcoming event with meeting points', function () {
        $event = Event::factory()->withMeetingPoints()->create();

        $this->actingAs($this->admin)->post(route('admin.events.publish', $event))->assertSessionHas('success');

        expect($event->fresh()->status)->toBe(EventStatus::Published)
            ->and($event->fresh()->published_at)->not->toBeNull();
    });

    it('refuses to publish an upcoming event without meeting points', function () {
        $event = Event::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.events.publish', $event))->assertSessionHas('error');

        expect($event->fresh()->status)->toBe(EventStatus::Draft);
    });

    it('allows publishing a past event without meeting points (archive)', function () {
        $event = Event::factory()->past()->create();

        $this->actingAs($this->admin)->post(route('admin.events.publish', $event))->assertSessionHas('success');
    });

    it('refuses to publish a cancelled event', function () {
        $event = Event::factory()->cancelled()->withMeetingPoints()->create();

        $this->actingAs($this->admin)->post(route('admin.events.publish', $event))->assertSessionHas('error');
    });

    it('unpublishes back to draft', function () {
        $event = Event::factory()->published()->create();

        $this->actingAs($this->admin)->post(route('admin.events.unpublish', $event));

        expect($event->fresh()->status)->toBe(EventStatus::Draft);
    });

    it('cancels with a reason and announces it', function () {
        EventBus::fake([EventCancelled::class]);
        $event = Event::factory()->published()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.events.cancel', $event), ['cancellation_reason' => 'Storm warning'])
            ->assertSessionHas('success');

        $event->refresh();
        expect($event->status)->toBe(EventStatus::Cancelled)
            ->and($event->cancellation_reason)->toBe('Storm warning');
        EventBus::assertDispatched(EventCancelled::class, fn ($e) => $e->event->is($event));
    });

    it('requires a cancellation reason', function () {
        $event = Event::factory()->published()->create();

        $this->actingAs($this->admin)->post(route('admin.events.cancel', $event), ['cancellation_reason' => ''])
            ->assertSessionHasErrors('cancellation_reason');
    });

    it('duplicates an event as a draft with its meeting points and its own image copy', function () {
        Storage::disk('public')->put('events/original.jpg', 'img');
        $event = Event::factory()->published()->withMeetingPoints(2)->create(['cover_image_path' => 'events/original.jpg']);

        $this->actingAs($this->admin)->post(route('admin.events.duplicate', $event))->assertRedirect();

        $copy = Event::latest('id')->firstOrFail();
        expect($copy->id)->not->toBe($event->id)
            ->and($copy->status)->toBe(EventStatus::Draft)
            ->and($copy->title)->toBe($event->title.' (copy)')
            ->and($copy->slug)->not->toBe($event->slug)
            ->and($copy->meetingPoints)->toHaveCount(2)
            ->and($copy->cover_image_path)->not->toBe('events/original.jpg');
        Storage::disk('public')->assertExists($copy->cover_image_path);
    });

    it('soft deletes drafts but not published events', function () {
        $draft = Event::factory()->create();
        $published = Event::factory()->published()->create();

        $this->actingAs($this->admin)->delete(route('admin.events.destroy', $draft))->assertRedirect(route('admin.events.index'));
        $this->actingAs($this->admin)->delete(route('admin.events.destroy', $published))->assertSessionHas('error');

        $this->assertSoftDeleted($draft);
        $this->assertNotSoftDeleted($published);
    });
});

describe('authorization', function () {
    it('denies roles without events.manage', function (Role $role) {
        $user = userWithRole($role);
        $event = Event::factory()->create();

        $this->actingAs($user)->get(route('admin.events.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.events.edit', $event))->assertForbidden();
        $this->actingAs($user)->post(route('admin.events.store'), eventPayload())->assertForbidden();
        $this->actingAs($user)->post(route('admin.events.publish', $event))->assertForbidden();
    })->with([Role::Editor, Role::Guide]);

    it('lets super admins manage events', function () {
        $this->actingAs(userWithRole(Role::SuperAdmin))->get(route('admin.events.create'))->assertOk();
    });
});

it('recalculates difficulty for all events via artisan', function () {
    $event = Event::factory()->create(['distance_km' => 8, 'elevation_gain_m' => 220, 'elevation_loss_m' => 320, 'effort_score' => 1]);

    $this->artisan('events:recalculate-difficulty')->assertSuccessful();

    expect($event->fresh()->effort_score)->toBe(90);
});

function gpxUpload(string $name = 'route.gpx'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, <<<'GPX'
        <?xml version="1.0" encoding="UTF-8"?>
        <gpx version="1.1" creator="test" xmlns="http://www.topografix.com/GPX/1/1">
          <trk><trkseg>
            <trkpt lat="34.29001" lon="35.95001"><ele>1500</ele></trkpt>
            <trkpt lat="34.29502" lon="35.96003"><ele>1620</ele></trkpt>
            <trkpt lat="34.30003" lon="35.97004"><ele>1580</ele></trkpt>
          </trkseg></trk>
        </gpx>
        GPX);
}

describe('detail page fields', function () {
    it('saves the itinerary and photo credit', function () {
        $this->actingAs($this->admin)->post(route('admin.events.store'), eventPayload([
            'itinerary' => "07:30 Departure\n\n09:00 Start\nLunch\nLunch",
            'photo_credit' => '  Jane Hiker ',
        ]))->assertSessionHasNoErrors();

        $event = Event::firstOrFail();
        expect($event->itinerary)->toBe(['07:30 Departure', '09:00 Start', 'Lunch', 'Lunch'])
            ->and($event->photo_credit)->toBe('Jane Hiker');
    });

    it('uploads, replaces and removes the GPX track', function () {
        $this->actingAs($this->admin)->post(route('admin.events.store'), eventPayload(['gpx_file' => gpxUpload()]))
            ->assertSessionHasNoErrors();
        $event = Event::firstOrFail();
        $first = $event->gpx_path;
        Storage::disk('public')->assertExists($first);
        expect($event->route_points)->toBe([[34.29001, 35.95001], [34.29502, 35.96003], [34.30003, 35.97004]]);

        $this->actingAs($this->admin)->put(route('admin.events.update', $event), eventPayload([
            'slug' => $event->slug,
            'gpx_file' => gpxUpload('new.gpx'),
        ]));
        $second = $event->fresh()->gpx_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        // Saving without a new file keeps the track.
        $this->actingAs($this->admin)->put(route('admin.events.update', $event), eventPayload(['slug' => $event->slug]));
        expect($event->fresh()->gpx_path)->toBe($second);

        $this->actingAs($this->admin)->put(route('admin.events.update', $event), eventPayload([
            'slug' => $event->slug,
            'remove_gpx' => '1',
        ]));
        expect($event->fresh())->gpx_path->toBeNull()->route_points->toBeNull();
        Storage::disk('public')->assertMissing($second);
    });

    it('rejects GPX files without a readable route', function (UploadedFile $file) {
        $this->actingAs($this->admin)
            ->post(route('admin.events.store'), eventPayload(['gpx_file' => $file]))
            ->assertSessionHasErrors('gpx_file');

        expect(Event::count())->toBe(0);
    })->with([
        'wrong extension' => fn () => UploadedFile::fake()->create('route.pdf', 10, 'application/pdf'),
        'not xml' => fn () => UploadedFile::fake()->createWithContent('route.gpx', 'hello'),
        'no points' => fn () => UploadedFile::fake()->createWithContent('route.gpx', '<gpx><trk><trkseg></trkseg></trk></gpx>'),
    ]);

    it('copies the GPX track when duplicating', function () {
        Storage::disk('public')->put('events/gpx/original.gpx', '<gpx/>');
        $event = Event::factory()->create(['gpx_path' => 'events/gpx/original.gpx', 'route_points' => [[34.1, 35.1], [34.2, 35.2]]]);

        $this->actingAs($this->admin)->post(route('admin.events.duplicate', $event))->assertRedirect();

        $copy = Event::latest('id')->firstOrFail();
        expect($copy->gpx_path)->not->toBe('events/gpx/original.gpx')
            ->and($copy->route_points)->toBe([[34.1, 35.1], [34.2, 35.2]]);
        Storage::disk('public')->assertExists($copy->gpx_path);
    });

    it('links published events to their public page', function () {
        $event = Event::factory()->published()->create();
        $draft = Event::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.events.edit', $event))->assertSee(route('events.show', $event));
        $this->actingAs($this->admin)->get(route('admin.events.edit', $draft))->assertDontSee('View on site');
    });
});
