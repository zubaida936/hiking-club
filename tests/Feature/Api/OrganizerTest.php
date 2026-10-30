<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Registrations\Enums\Attendance;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;

/**
 * An app token for a user with this role (staff log in to the app with their phone, like members).
 */
function organizerToken(Role $role): string
{
    $user = userWithRole($role, ['phone' => '+9617000'.random_int(1000, 9999)]);

    return $user->createToken('Phone')->plainTextToken;
}

function hikeOn(int $daysFromToday, array $attributes = []): Event
{
    return Event::factory()->published()->create($attributes + [
        'starts_on' => today(config('site.timezone'))->addDays($daysFromToday)->toDateString(),
    ]);
}

describe('organizer mode', function () {
    it('tells the app who gets the Organizer tab', function (Role $role, ?array $organizer) {
        $this->withToken(organizerToken($role))->getJson(route('api.v1.me'))
            ->assertOk()
            ->assertJsonPath('data.organizer', $organizer);
    })->with([
        'guide' => [Role::Guide, ['can_mark_attendance' => true]],
        'admin' => [Role::Admin, ['can_mark_attendance' => true]],
        'content editor' => [Role::Editor, null],
        'member' => [Role::Member, null],
    ]);

    it('refuses everyone without the CMS permissions', function (Role $role) {
        $token = organizerToken($role);
        $event = hikeOn(0);
        $registration = EventRegistration::factory()->for($event)->create();

        $this->getJson(route('api.v1.organizer.events'))->assertUnauthorized();
        $this->withToken($token)->getJson(route('api.v1.organizer.events'))->assertForbidden();
        $this->withToken($token)->getJson(route('api.v1.organizer.registrations', $event))->assertForbidden();
        $this->withToken($token)->putJson(route('api.v1.organizer.attendance', $registration->reference), ['attendance' => 'attended'])->assertForbidden();

        expect($registration->fresh()->attendance)->toBeNull();
    })->with([Role::Member, Role::Editor]);
});

describe('hikes and check-in lists', function () {
    it('lists the hikes from a week ago to a week ahead with their check-in counts', function () {
        hikeOn(-8);
        $lastWeek = hikeOn(-7);
        $today = hikeOn(0);
        $nextWeek = hikeOn(7);
        hikeOn(8);
        EventRegistration::factory()->for($today)->count(2)->create();
        EventRegistration::factory()->for($today)->create(['attendance' => Attendance::Attended]);
        EventRegistration::factory()->for($today)->waitlisted()->create();

        $this->withToken(organizerToken(Role::Guide))->getJson(route('api.v1.organizer.events'))
            ->assertOk()
            ->assertJsonPath('data.*.event.slug', [$lastWeek->slug, $today->slug, $nextWeek->slug])
            ->assertJsonPath('data.1.confirmed', 3)
            ->assertJsonPath('data.1.checked_in', 1)
            ->assertJsonPath('data.1.check_in_open', true)
            ->assertJsonPath('data.2.check_in_open', false);
    });

    it('gives the hike\'s confirmed and pending bookings by pickup time, with what guides need', function () {
        $event = hikeOn(0);
        $late = EventRegistration::factory()->for($event)->create(['name' => 'Ziad', 'pickup_point' => 'Jounieh', 'pickup_time' => '08:00:00', 'paid_at' => now()]);
        $early = EventRegistration::factory()->for($event)->create(['name' => 'Maya', 'pickup_time' => '07:30:00', 'emergency_contact_name' => 'Sami', 'emergency_contact_phone' => '+9613444555']);
        $pending = EventRegistration::factory()->for($event)->pending()->create(['name' => 'Karim', 'pickup_time' => '07:30:00']);
        EventRegistration::factory()->for($event)->waitlisted()->create();
        EventRegistration::factory()->for($event)->cancelled()->create();

        $this->withToken(organizerToken(Role::Guide))->getJson(route('api.v1.organizer.registrations', $event))
            ->assertOk()
            ->assertJsonPath('data.*.reference', [$pending->reference, $early->reference, $late->reference])
            ->assertJsonPath('data.0.can_check_in', false)
            ->assertJsonPath('data.1.emergency_contact', ['name' => 'Sami', 'phone' => '+9613444555'])
            ->assertJsonPath('data.1.payment.state', 'due')
            ->assertJsonPath('data.2.payment.state', 'paid')
            ->assertJsonPath('data.2.pickup', ['point' => 'Jounieh', 'time' => '08:00'])
            ->assertJsonPath('data.2.attendance', null);
    });
});

describe('marking attendance', function () {
    it('marks, resends and clears attendance like the CMS', function () {
        $registration = EventRegistration::factory()->for(hikeOn(0))->create();
        $token = organizerToken(Role::Guide);
        $url = route('api.v1.organizer.attendance', $registration->reference);

        $this->withToken($token)->putJson($url, ['attendance' => 'attended'])
            ->assertOk()
            ->assertJsonPath('data.attendance', 'attended');
        // A phone that was offline sends the same mark again: nothing changes, no error.
        $this->withToken($token)->putJson($url, ['attendance' => 'attended'])->assertOk();
        expect($registration->fresh())
            ->attendance->toBe(Attendance::Attended)
            ->updated_by->not->toBeNull();

        $this->withToken($token)->putJson($url, ['attendance' => 'no_show'])->assertJsonPath('data.attendance', 'no_show');
        $this->withToken($token)->putJson($url, ['attendance' => null])->assertJsonPath('data.attendance', null);
    });

    it('gives points for attendance, like the CMS', function () {
        $registration = EventRegistration::factory()->for(hikeOn(0, ['distance_km' => 12]))->create(['paid_at' => now()]);

        $this->withToken(organizerToken(Role::Guide))
            ->putJson(route('api.v1.organizer.attendance', $registration->reference), ['attendance' => 'attended'])
            ->assertOk();

        expect($registration->fresh()->hiker->totalPoints())->toBeGreaterThan(0);
    });

    it('follows the CMS rules', function () {
        $token = organizerToken(Role::Guide);
        $future = EventRegistration::factory()->for(hikeOn(3))->create();
        $pending = EventRegistration::factory()->for(hikeOn(0))->pending()->create();

        $this->withToken($token)->putJson(route('api.v1.organizer.attendance', $future->reference), ['attendance' => 'attended'])
            ->assertUnprocessable()->assertJsonStructure(['message']);
        $this->withToken($token)->putJson(route('api.v1.organizer.attendance', $pending->reference), ['attendance' => 'attended'])
            ->assertUnprocessable()->assertJsonStructure(['message']);
        $this->withToken($token)->putJson(route('api.v1.organizer.attendance', $pending->reference), ['attendance' => 'maybe'])
            ->assertUnprocessable()->assertJsonValidationErrors('attendance');
        $this->withToken($token)->putJson(route('api.v1.organizer.attendance', $pending->reference), [])
            ->assertJsonValidationErrors('attendance');
        $this->withToken($token)->putJson(route('api.v1.organizer.attendance', 'NOPE1234'), ['attendance' => 'attended'])->assertNotFound();
    });
});
