<?php

use App\Models\Event;

function announcementBar(string $uri = '/'): ?string
{
    $html = test()->get($uri)->assertOk()->getContent();

    return preg_match('#<div[^>]*aria-label="Next event".*?</div>\s*</div>#s', $html, $m) ? $m[0] : null;
}

it('announces the next published upcoming event with a link', function () {
    $next = Event::factory()->published()->create(['title' => 'Qadisha Valley Descent', 'starts_on' => '2026-11-07']);
    Event::factory()->published()->create(['title' => 'Later Hike', 'starts_on' => '2026-12-01']);
    Event::factory()->create(['title' => 'Draft Hike', 'starts_on' => '2026-10-30']);
    Event::factory()->published()->cancelled()->create(['title' => 'Cancelled Hike', 'starts_on' => '2026-10-29']);
    Event::factory()->published()->create(['title' => 'Past Hike', 'starts_on' => '2026-10-01']);

    $this->travelTo('2026-10-22 10:00');

    expect(announcementBar())
        ->toContain('Next hike:')
        ->toContain('Qadisha Valley Descent')
        ->toContain('Sat 7 Nov')
        ->toContain('View Event')
        ->toContain('href="'.route('events.show', $next).'"')
        ->not->toContain('Later Hike')
        ->not->toContain('Draft Hike')
        ->not->toContain('Cancelled Hike')
        ->not->toContain('Past Hike');
});

it('says today or tomorrow for events that close', function (string $startsOn, string $expected) {
    $this->travelTo('2026-10-22 10:00');
    Event::factory()->published()->create(['starts_on' => $startsOn]);

    expect(announcementBar())->toContain($expected);
})->with([
    'today' => ['2026-10-22', 'Today'],
    'tomorrow' => ['2026-10-23', 'Tomorrow'],
]);

it('is hidden when no event is coming up', function () {
    Event::factory()->published()->past()->create();

    expect(announcementBar())->toBeNull();
});

it('shows on every public page', function () {
    Event::factory()->published()->create(['title' => 'Tannourine Cedars Loop']);

    expect(announcementBar(route('about')))->toContain('Tannourine Cedars Loop');
});

it('escapes the event title', function () {
    Event::factory()->published()->create(['title' => '<script>alert(1)</script> Hike']);

    expect(announcementBar())->toContain('&lt;script&gt;')->not->toContain('<script>alert(1)');
});
