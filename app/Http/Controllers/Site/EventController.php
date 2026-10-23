<?php

namespace App\Http\Controllers\Site;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Past events shown per "Load More" step.
     */
    public const PAST_PER_PAGE = 6;

    /**
     * "Load More" re-renders the past list with page × PAST_PER_PAGE items, so the page
     * also works without JavaScript; this caps how much a single request can ask for.
     */
    private const MAX_PAST_PAGES = 50;

    public function index(Request $request): View
    {
        // Unknown filter values are ignored rather than rejected: these are shareable public URLs.
        $level = DifficultyLevel::tryFrom($request->integer('level'));
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('month')) ? Carbon::createFromFormat('!Y-m', (string) $request->query('month')) : null;
        $page = min(max($request->integer('page', 1), 1), self::MAX_PAST_PAGES);

        $filter = fn (Builder $q) => $q
            ->when($level, fn (Builder $q) => $q->where('difficulty_level', $level))
            ->when($month, fn (Builder $q) => $q->whereBetween('starts_on', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()]));

        /** @var Builder<Event> $published */
        $published = Event::published()->where($filter);

        $upcoming = (clone $published)->upcoming()->orderBy('starts_on')->orderBy('id')->get();
        $pastTotal = (clone $published)->past()->count();
        $past = (clone $published)->past()->orderByDesc('starts_on')->orderByDesc('id')->limit($page * self::PAST_PER_PAGE)->get();

        return view('site.events.index', [
            'upcoming' => $upcoming,
            'past' => $past,
            'nextPage' => $past->count() < $pastTotal && $page < self::MAX_PAST_PAGES ? $page + 1 : null,
            'levels' => DifficultyLevel::cases(),
            'months' => $this->months(),
            'level' => $level,
            'month' => $month?->format('Y-m'),
        ]);
    }

    public function show(Event $event): View
    {
        // Drafts (including unpublished events) have no public page.
        abort_unless($event->isVisibleOnSite(), 404);

        $event->load('meetingPoints')->loadCount(['registrations as active_registrations_count' => fn ($q) => $q->active()]);

        return view('site.events.show', [
            'event' => $event,
            'whatsappMessage' => "Hi! I have a question about \"{$event->title}\" on {$event->starts_on->format('D, j M Y')}: ".route('events.show', $event),
        ]);
    }

    /**
     * Months that have at least one published event, newest first, for the month filter.
     *
     * @return array<string, string> "2026-11" => "November 2026"
     */
    private function months(): array
    {
        return Event::published()
            ->orderByDesc('starts_on')
            ->pluck('starts_on')
            ->mapWithKeys(fn (Carbon $date) => [$date->format('Y-m') => $date->format('F Y')])
            ->all();
    }
}
