<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Events\Actions\PostEventUpdate;
use App\Domain\Registrations\Actions\MarkAttendance;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Rewards\Actions\RecordPerkUse;
use App\Domain\Rewards\Exceptions\RewardException;
use App\Domain\Rewards\HikerRewards;
use App\Domain\Rewards\PerkCode;
use App\Domain\Rewards\Reward;
use App\Domain\Rewards\RewardCatalog;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CheckInResource;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
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

    /**
     * Send a hike update.
     *
     * A weather, route or timing update for everyone booked on the hike (and its waitlist): shown on the hike page
     * and every booking page, and emailed to each booking with an email address. Same action and permission as
     * CMS → Registrations → "Send update to hikers". `emailed_count` says how many emails went out.
     */
    public function postUpdate(Request $request, Event $event, PostEventUpdate $action): JsonResponse
    {
        Gate::authorize('notifyHikers', EventRegistration::class);

        $message = $request->validate(['message' => ['required', 'string', 'max:2000']])['message'];

        /** @var User $actor */
        $actor = $request->user();
        $update = $action->handle($event, trim((string) $message), $actor);

        return response()->json(['data' => [
            'message' => (string) $update->message,
            'posted_at' => $update->created_at->toIso8601String(),
            'emailed_count' => (int) $update->emailed_count,
        ]], 201);
    }

    /**
     * Check a perk code.
     *
     * The member's name and phone, the perk, and whether it can be handed over now (otherwise `unavailable_reason`
     * says why: not unlocked yet, or already handed over). A code that isn't a genuine perk code gives a 422.
     */
    public function perk(string $code, PerkCode $codes, RewardCatalog $catalog, HikerRewards $rewards): JsonResponse
    {
        Gate::authorize('handOverPerk', Hiker::class);

        [$hiker, $reward] = $this->resolvePerk($code, $codes, $catalog);

        return response()->json(['data' => $this->perkState($hiker, $reward, $rewards)]);
    }

    /**
     * Hand over a perk.
     *
     * Records it in the Rewards used log (same action as the CMS), with an optional note. A perk that was already
     * handed over, or isn't unlocked, gives a 422 with the reason; two organizers scanning at once can't both
     * hand it over.
     */
    public function handOverPerk(Request $request, string $code, PerkCode $codes, RewardCatalog $catalog, HikerRewards $rewards, RecordPerkUse $action): JsonResponse
    {
        Gate::authorize('handOverPerk', Hiker::class);

        [$hiker, $reward] = $this->resolvePerk($code, $codes, $catalog);
        $note = $request->validate(['note' => ['nullable', 'string', 'max:200']])['note'] ?? null;

        /** @var User $actor */
        $actor = $request->user();
        $action->handle($hiker, $reward->key, $note, $actor);

        return response()->json(['data' => $this->perkState($hiker, $reward, $rewards)], 201);
    }

    /**
     * @return array{0: Hiker, 1: Reward}
     *
     * @throws RewardException
     */
    private function resolvePerk(string $code, PerkCode $codes, RewardCatalog $catalog): array
    {
        $parsed = $codes->parse($code);
        $hiker = $parsed === null ? null : Hiker::find($parsed['hiker_id']);
        $reward = $parsed === null ? null : $catalog->find($parsed['reward']);

        if ($hiker === null || $reward === null || $reward->kind !== Reward::PERK) {
            throw RewardException::invalidPerkCode();
        }

        return [$hiker, $reward];
    }

    /**
     * @return array{member: array{name: string, phone: string}, reward: array{key: string, label: string, rank: string}, available: bool, unavailable_reason: string|null, handed_over_at: string|null}
     */
    private function perkState(Hiker $hiker, Reward $reward, HikerRewards $rewards): array
    {
        $state = null;
        foreach ($rewards->statesFor($hiker) as $candidate) {
            if ($candidate['reward']->key === $reward->key) {
                $state = $candidate;
            }
        }

        $lastUse = $state === null ? null : $state['redemptions']->first();
        $available = $state !== null && $state['unlocked'] && $state['uses_left'] > 0;

        return [
            'member' => ['name' => (string) $hiker->name, 'phone' => (string) $hiker->phone],
            'reward' => ['key' => $reward->key, 'label' => $reward->label, 'rank' => $reward->tier->label()],
            'available' => $available,
            'unavailable_reason' => $available ? null : (string) ($state['locked_reason'] ?? 'Already handed over.'),
            // When it was last handed over (newest first), null if never.
            'handed_over_at' => $lastUse === null ? null : $lastUse->created_at->toIso8601String(),
        ];
    }
}
