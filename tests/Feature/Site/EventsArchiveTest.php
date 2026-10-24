<?php

use App\Http\Controllers\Site\EventController;
use App\Models\Event;

beforeEach(fn () => $this->travelTo('2026-10-24 10:00'));

/**
 * One published past hike per month, from $from back $count months ("Hike 2026-09"...).
 */
function hikesPerMonth(string $from, int $count): void
{
    foreach (range(0, $count - 1) as $i) {
        $date = Carbon\Carbon::parse($from)->subMonthsNoOverflow($i);
        Event::factory()->published()->create(['title' => 'Hike '.$date->format('Y-m'), 'starts_on' => $date->toDateString()]);
    }
}

it('groups past hikes by month, newest month first', function () {
    Event::factory()->published()->create(['title' => 'Late October hike', 'starts_on' => '2026-10-17']);
    Event::factory()->published()->create(['title' => 'Early October hike', 'starts_on' => '2026-10-03']);
    Event::factory()->published()->create(['title' => 'September hike', 'starts_on' => '2026-09-12']);
    Event::factory()->published()->create(['title' => 'Upcoming hike', 'starts_on' => '2026-11-07']);
    Event::factory()->create(['title' => 'Draft hike', 'starts_on' => '2026-10-10']);
    Event::factory()->published()->cancelled()->create(['title' => 'Cancelled hike', 'starts_on' => '2026-10-11']);

    $response = $this->get(route('events.archive'))
        ->assertOk()
        ->assertSee('Earlier adventures')
        ->assertSeeInOrder(['October 2026', '2 hikes', 'Late October hike', 'Early October hike', 'September 2026', '1 hike', 'September hike'])
        ->assertSee('<option value="2026-10" >October 2026 (2)</option>', false)
        ->assertDontSee('Draft hike')
        ->assertDontSee('Cancelled hike')
        ->assertDontSee('Show more months');

    // The upcoming hike is in the site-wide "Next hike" bar, but not in the archive itself.
    expect(Str::after($response->getContent(), 'id="archive"'))->not->toContain('Upcoming hike');
});

it('shows three months at a time with Show more months', function () {
    hikesPerMonth('2026-09-15', 7);
    $perStep = EventController::ARCHIVE_MONTHS_PER_STEP;
    expect($perStep)->toBe(3);

    $this->get(route('events.archive'))
        ->assertSeeInOrder(['Hike 2026-09', 'Hike 2026-08', 'Hike 2026-07'])
        ->assertDontSee('<h3 class="mt-1 font-display text-xl font-bold uppercase tracking-wide text-brand-900">Hike 2026-06</h3>', false)
        ->assertSee(route('events.archive', ['step' => 2]))
        ->assertSee('Show more months');

    $this->get(route('events.archive', ['step' => 2]))
        ->assertSeeInOrder(['Hike 2026-09', 'Hike 2026-06', 'Hike 2026-04'])
        ->assertSee(route('events.archive', ['step' => 3]))
        ->assertSee('noindex');

    $this->get(route('events.archive', ['step' => 3]))
        ->assertSee('Hike 2026-03')
        ->assertDontSee('Show more months');

    // Out-of-range steps are clamped rather than rejected.
    $this->get(route('events.archive', ['step' => 999]))->assertOk()->assertSee('Hike 2026-03');
});

it('shows a single month from the selector, ignoring months without hikes', function () {
    hikesPerMonth('2026-09-15', 4);

    $this->get(route('events.archive', ['month' => '2026-07']))
        ->assertOk()
        ->assertSee('Hike 2026-07')
        ->assertDontSee('Hike 2026-09')
        ->assertSee('<option value="2026-07" selected>July 2026 (1)</option>', false)
        ->assertSee('All months')
        ->assertDontSee('Show more months');

    $this->get(route('events.archive', ['month' => '2020-01']))->assertOk()->assertSee('Hike 2026-09');
    $this->get(route('events.archive', ['month' => 'nonsense']))->assertOk()->assertSee('Hike 2026-09');
});

it('has a friendly empty state', function () {
    $this->get(route('events.archive'))->assertOk()->assertSee('Our first adventures are still ahead of us.');
});

it('is linked from the events page and listed in the sitemap', function () {
    Event::factory()->published()->past()->create();

    $this->get(route('events.index'))->assertSee(route('events.archive'))->assertSee('Browse earlier adventures by month');
    $this->get(route('sitemap'))->assertSee('<loc>'.route('events.archive').'</loc>', false);
});
