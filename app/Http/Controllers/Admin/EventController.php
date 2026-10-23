<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Events\Actions\CancelEvent;
use App\Domain\Events\Actions\DeleteEvent;
use App\Domain\Events\Actions\DuplicateEvent;
use App\Domain\Events\Actions\PublishEvent;
use App\Domain\Events\Actions\SaveEvent;
use App\Domain\Events\Actions\UnpublishEvent;
use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Events\Enums\EventStatus;
use App\Domain\Events\Exceptions\EventException;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Event::class);

        $status = EventStatus::tryFrom((string) $request->query('status'));
        $level = DifficultyLevel::tryFrom($request->integer('level'));
        $when = in_array($request->query('when'), ['upcoming', 'past'], true) ? $request->query('when') : null;

        $events = Event::query()
            ->withCount('meetingPoints')
            ->withSeatCounts()
            ->search($request->string('q')->trim()->toString() ?: null)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($level, fn ($q) => $q->where('difficulty_level', $level))
            ->when($when === 'upcoming', fn ($q) => $q->upcoming()->orderBy('starts_on'))
            ->when($when !== 'upcoming', fn ($q) => $q->when($when === 'past', fn ($q) => $q->past())->orderByDesc('starts_on'))
            ->paginate(config('cms.per_page'))
            ->withQueryString();

        return view('admin.events.index', [
            'events' => $events,
            'statuses' => EventStatus::cases(),
            'levels' => DifficultyLevel::cases(),
        ]);
    }

    public function create(DifficultyCalculator $difficulty): View
    {
        Gate::authorize('create', Event::class);

        return view('admin.events.create', [
            'event' => new Event([
                'capacity' => 30,
                'waitlist_enabled' => true,
                'fee_cents' => 0,
                'elevation_gain_m' => 0,
                'elevation_loss_m' => 0,
                'included' => ['Transportation', 'Guiding', 'Hiking assistants'],
            ]),
            'calculator' => $difficulty->toArray(),
        ]);
    }

    public function store(EventRequest $request, SaveEvent $action): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $event = $action->handle($request->toData(), $request->user());

        return redirect()->route('admin.events.edit', $event)->with('success', 'Event created as a draft.');
    }

    public function edit(Request $request, Event $event, DifficultyCalculator $difficulty): View
    {
        Gate::authorize('update', $event);

        $event->load('meetingPoints', 'creator', 'editor');

        if ($request->user()?->can(Permission::ViewRegistrations->value)) {
            $event->loadSeatCount()->loadCount(['registrations as waitlisted_count' => fn ($q) => $q->where('status', RegistrationStatus::Waitlisted)]);
        }

        return view('admin.events.edit', ['event' => $event, 'calculator' => $difficulty->toArray()]);
    }

    public function update(EventRequest $request, Event $event, SaveEvent $action): RedirectResponse
    {
        Gate::authorize('update', $event);

        $action->handle($request->toData(), $request->user(), $event);

        return redirect()->route('admin.events.edit', $event)->with('success', 'Event saved.');
    }

    public function destroy(Event $event, DeleteEvent $action): RedirectResponse
    {
        Gate::authorize('delete', $event);

        return $this->attempt(
            fn () => $action->handle($event),
            redirect()->route('admin.events.index'),
            'Event deleted.',
        );
    }

    public function publish(Request $request, Event $event, PublishEvent $action): RedirectResponse
    {
        Gate::authorize('update', $event);

        return $this->attempt(fn () => $action->handle($event, $request->user()), back(), 'Event published.');
    }

    public function unpublish(Request $request, Event $event, UnpublishEvent $action): RedirectResponse
    {
        Gate::authorize('update', $event);

        $action->handle($event, $request->user());

        return back()->with('success', 'Event moved back to draft.');
    }

    public function cancel(Request $request, Event $event, CancelEvent $action): RedirectResponse
    {
        Gate::authorize('update', $event);

        $validated = $request->validate(['cancellation_reason' => ['required', 'string', 'max:500']]);

        return $this->attempt(
            fn () => $action->handle($event, $validated['cancellation_reason'], $request->user()),
            back(),
            'Event cancelled.',
        );
    }

    public function duplicate(Request $request, Event $event, DuplicateEvent $action): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $copy = $action->handle($event->load('meetingPoints'), $request->user());

        return redirect()->route('admin.events.edit', $copy)->with('success', 'Event duplicated. Update the date and publish when ready.');
    }

    /**
     * Run a domain action and turn rule violations into a flash error.
     */
    private function attempt(callable $action, RedirectResponse $onSuccess, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (EventException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $onSuccess->with('success', $message);
    }
}
