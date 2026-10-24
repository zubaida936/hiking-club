<?php

namespace App\Http\Controllers\Site;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Http\Controllers\Controller;
use App\Models\Album;
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

    /**
     * Months of the archive shown per "Show more months" step (same no-JS approach as Load More).
     */
    public const ARCHIVE_MONTHS_PER_STEP = 3;

    private const MAX_ARCHIVE_STEPS = 40;

    /**
     * "Earlier adventures": past hikes grouped by month, newest month first.
     * ?month=YYYY-MM shows a single month; ?step=N shows N × ARCHIVE_MONTHS_PER_STEP months.
     */
    public function archive(Request $request): View
    {
        // Every month that had published hikes, newest first, with the number of hikes.
        /** @var array<string, int> $months */
        $months = Event::published()->past()
            ->selectRaw("DATE_FORMAT(starts_on, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->pluck('total', 'ym')
            ->map(fn ($total) => (int) $total)
            ->all();

        $selected = array_key_exists((string) $request->query('month'), $months) ? (string) $request->query('month') : null;
        $step = min(max($request->integer('step', 1), 1), self::MAX_ARCHIVE_STEPS);
        $shown = $selected !== null ? [$selected] : array_slice(array_keys($months), 0, $step * self::ARCHIVE_MONTHS_PER_STEP);

        $events = $shown === [] ? collect() : Event::published()->past()
            ->whereBetween('starts_on', [
                Carbon::createFromFormat('!Y-m', (string) end($shown))->startOfMonth()->toDateString(),
                Carbon::createFromFormat('!Y-m', $shown[0])->endOfMonth()->toDateString(),
            ])
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (Event $event) => $event->starts_on->format('Y-m'));

        return view('site.events.archive', [
            'groups' => collect($shown)->mapWithKeys(fn (string $ym) => [$ym => $events->get($ym, collect())])->filter(fn ($group) => $group->isNotEmpty()),
            'months' => $months,
            'selected' => $selected,
            'nextStep' => $selected === null && count($shown) < count($months) && $step < self::MAX_ARCHIVE_STEPS ? $step + 1 : null,
        ]);
    }

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

        $upcoming = (clone $published)->upcoming()->withSeatCounts()->orderBy('starts_on')->orderBy('id')->get();
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

        $event->load(['meetingPoints', 'updates'])->loadSeatCount();

        return view('site.events.show', [
            'event' => $event,
            // Past hikes link to their photos once an album is published.
            'album' => Album::published()->whereBelongsTo($event)->has('photos')->latest('id')->first(['id', 'slug']),
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
