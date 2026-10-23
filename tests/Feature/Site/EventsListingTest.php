<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Http\Controllers\Site\EventController;
use App\Models\Event;
use Illuminate\Support\Facades\Route;

it('lists published upcoming events soonest first with their card details', function () {
    Event::factory()->published()->create(['title' => 'Later Hike', 'starts_on' => now()->addDays(20)->toDateString()]);
    $soon = Event::factory()->published()->create([
        'title' => 'Qadisha Valley Descent',
        'starts_on' => now()->addDays(5)->toDateString(),
        'season_tag' => '🍂 Autumn Trail',
        'distance_km' => 12.5,
        'effort_score' => 37,
        'fee_cents' => 3000,
        'is_xtreme' => true,
    ]);
    Event::factory()->published()->create(['title' => 'Sunrise Hike', 'starts_on' => today()->toDateString()]);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee('<title>Events · '.e(config('app.name')).'</title>', false)
        ->assertSeeInOrder(['Upcoming Events', 'Sunrise Hike', 'Qadisha Valley Descent', 'Later Hike'])
        ->assertSee('🍂 Autumn Trail')
        ->assertSee('12.5 km')
        ->assertSee($soon->starts_on->format('D, j M Y'))
        ->assertSee($soon->difficulty_level->label())
        ->assertSee('37')
        ->assertSee('$30')
        ->assertSee('X Event');
});

it('hides drafts and cancelled events', function () {
    Event::factory()->create(['title' => 'Draft Hike']);
    Event::factory()->published()->cancelled()->create(['title' => 'Cancelled Hike']);
    Event::factory()->published()->past()->cancelled()->create(['title' => 'Cancelled Past Hike']);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertDontSee('Draft Hike')
        ->assertDontSee('Cancelled Hike')
        ->assertDontSee('Cancelled Past Hike')
        ->assertSee('Our next adventure is being planned')
        ->assertDontSee('Past Events');
});

it('lists past events newest first below the upcoming ones', function () {
    Event::factory()->published()->create(['title' => 'Upcoming Hike']);
    Event::factory()->published()->create(['title' => 'Old Hike', 'starts_on' => now()->subMonths(3)->toDateString()]);
    Event::factory()->published()->create(['title' => 'Recent Hike', 'starts_on' => now()->subDays(3)->toDateString()]);

    $this->get(route('events.index'))
        ->assertSeeInOrder(['Upcoming Hike', 'Past Events', 'Recent Hike', 'Old Hike']);
});

it('loads more past events a page at a time', function () {
    $perPage = EventController::PAST_PER_PAGE;
    foreach (range(1, $perPage + 2) as $i) {
        Event::factory()->published()->create(['title' => "Past Hike #$i", 'starts_on' => now()->subDays($i)->toDateString()]);
    }

    $this->get(route('events.index'))
        ->assertSee("Past Hike #$perPage")
        ->assertDontSee('Past Hike #'.($perPage + 1))
        ->assertSee('Load More')
        ->assertSee('href="'.e(route('events.index', ['page' => 2])).'#past"', false);

    $this->get(route('events.index', ['page' => 2]))
        ->assertSee('Past Hike #1')
        ->assertSee('Past Hike #'.($perPage + 2))
        ->assertDontSee('Load More');
});

it('keeps the filters in the Load More link', function () {
    foreach (range(1, EventController::PAST_PER_PAGE + 1) as $i) {
        Event::factory()->published()->create(['starts_on' => now()->subDays($i)->toDateString(), 'difficulty_level' => DifficultyLevel::Endurance]);
    }

    $this->get(route('events.index', ['level' => 2]))
        ->assertSee('href="'.e(route('events.index', ['level' => 2, 'page' => 2])).'#past"', false);
});

it('filters by difficulty level', function () {
    // Big Climb comes first, so the announcement bar never names Easy Walk.
    Event::factory()->published()->create(['title' => 'Big Climb', 'difficulty_level' => DifficultyLevel::Advanced, 'starts_on' => now()->addDays(3)->toDateString()]);
    Event::factory()->published()->create(['title' => 'Easy Walk', 'difficulty_level' => DifficultyLevel::Foundation, 'starts_on' => now()->addDays(10)->toDateString()]);
    Event::factory()->published()->past()->create(['title' => 'Old Climb', 'difficulty_level' => DifficultyLevel::Advanced]);

    $this->get(route('events.index', ['level' => DifficultyLevel::Advanced->value]))
        ->assertOk()
        ->assertSee('Big Climb')
        ->assertSee('Old Climb')
        ->assertDontSee('Easy Walk')
        ->assertSee('Clear filters');
});

it('filters by month and only offers months that have events', function () {
    $this->travelTo('2026-10-23');
    // The announcement bar always shows the next hike; keep it from naming the ones under test.
    Event::factory()->published()->create(['title' => 'Weekend Hike', 'starts_on' => '2026-10-25']);
    Event::factory()->published()->create(['title' => 'November Hike', 'starts_on' => '2026-11-14']);
    Event::factory()->published()->create(['title' => 'December Hike', 'starts_on' => '2026-12-05']);
    Event::factory()->create(['title' => 'Draft Hike', 'starts_on' => '2027-01-09']);

    $this->get(route('events.index', ['month' => '2026-12']))
        ->assertOk()
        ->assertSee('December Hike')
        ->assertDontSee('November Hike')
        ->assertSee('<option value="2026-12" selected>December 2026</option>', false)
        ->assertSee('November 2026')
        ->assertDontSee('January 2027');
});

it('says so when no event matches the filters', function () {
    Event::factory()->published()->create(['difficulty_level' => DifficultyLevel::Foundation]);

    $this->get(route('events.index', ['level' => DifficultyLevel::Xtreme->value]))
        ->assertSee('No upcoming hikes match these filters.')
        ->assertDontSee('Our next adventure is being planned');
});

it('ignores invalid filter values instead of failing', function (array $query) {
    Event::factory()->published()->create(['title' => 'Any Hike']);

    $this->get(route('events.index', $query))->assertOk()->assertSee('Any Hike');
})->with([
    'unknown level' => [['level' => 9]],
    'text level' => [['level' => 'hard']],
    'bad month' => [['month' => '2026-13']],
    'not a month' => [['month' => 'november']],
    'negative page' => [['page' => -4]],
    'huge page' => [['page' => 999999]],
]);

it('links each card to the event page once it exists', function () {
    $event = Event::factory()->published()->create(['slug' => 'qadisha-valley']);

    $this->get(route('events.index'))->assertDontSee('View Details');

    Route::get('events/{event:slug}', fn () => 'event')->name('events.show');
    Route::getRoutes()->refreshNameLookups();

    $this->get(route('events.index'))
        ->assertSee('View Details')
        ->assertSee('href="'.route('events.show', $event).'"', false);
});

it('is indexable and listed in the sitemap', function () {
    $this->get(route('events.index'))
        ->assertSee('<link rel="canonical" href="'.route('events.index').'">', false)
        ->assertDontSee('name="robots"', false);

    $this->get('/sitemap.xml')->assertSee('<loc>'.route('events.index').'</loc>', false);
});
