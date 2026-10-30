<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Registrations\Actions\MarkAttendance;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CheckInResource;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Organizer tools in the app (staff only): the hikes around today, their check-in lists, and attendance.
 * Same permissions as the CMS (registrations.view, attendance.manage) and the same MarkAttendance action,
 * so attendance gives points exactly like in the CMS. The rest of the CMS stays on the website.
 */
class OrganizerController extends Controller
{
    /** Hikes shown to organizers: this many days before and after today. */
    private const DAYS_AROUND = 7;

    /**
     * Hikes to check in.
     *
     * Published hikes from a week ago to a week ahead, soonest first, with how many confirmed hikers there are
     * and how many are checked in. `check_in_open` is false before the hike day (attendance can't be marked yet).
     */
    public function events(): JsonResponse
    {
        Gate::authorize('viewAny', EventRegistration::class);

        $today = today((string) config('site.timezone'));
        $events = Event::published()
            ->whereBetween('starts_on', [$today->copy()->subDays(self::DAYS_AROUND)->toDateString(), $today->copy()->addDays(self::DAYS_AROUND)->toDateString()])
            ->withSeatCounts()
            ->withCount([
                'registrations as confirmed_count' => fn ($query) => $query->where('status', RegistrationStatus::Confirmed),
                'registrations as attended_count' => fn ($query) => $query->where('status', RegistrationStatus::Confirmed)->where('attendance', Attendance::Attended),
            ])
            ->orderBy('starts_on')->orderBy('id')
            ->get();

        // A loop with casts (not a map) so the API docs type every field.
        $items = [];
        foreach ($events as $event) {
            $items[] = [
                'event' => new EventResource($event),
                'confirmed' => (int) $event->getAttribute('confirmed_count'),
                'checked_in' => (int) $event->getAttribute('attended_count'),
                'check_in_open' => (bool) $event->hikeDayReached(),
            ];
        }

        return response()->json(['data' => $items]);
    }

    /**
     * Check-in list.
     *
     * The hike's confirmed bookings and those awaiting approval, by pickup time then name (waitlisted and
     * cancelled ones aren't coming). Save it on the phone: attendance can be marked offline and sent later.
     */
    public function registrations(Event $event): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', EventRegistration::class);

        $registrations = $event->registrations()
            ->whereIn('status', [RegistrationStatus::Confirmed, RegistrationStatus::Pending])
            ->orderByRaw('pickup_time is null, pickup_time')
            ->orderBy('name')
            ->get();
        // The amount due reads the hike's fee: they all share this one.
        $registrations->each->setRelation('event', $event);

        return CheckInResource::collection($registrations);
    }

    /**
     * Mark attendance.
     *
     * `attended`, `no_show`, or null to clear it. Same rules as the CMS: confirmed bookings only, from the hike
     * day on (otherwise 422 with a message). Sending the same value again changes nothing, so a phone can safely
     * resend marks it made offline. Points follow automatically.
     */
    public function attendance(Request $request, EventRegistration $registration, MarkAttendance $action): CheckInResource
    {
        Gate::authorize('markAttendance', $registration);

        $value = $request->validate(['attendance' => ['present', 'nullable', Rule::enum(Attendance::class)]])['attendance'] ?? null;

        /** @var User $actor */
        $actor = $request->user();
        $action->handle($registration, $value === null ? null : Attendance::from($value), $actor);

        return new CheckInResource($registration->refresh());
    }
}
