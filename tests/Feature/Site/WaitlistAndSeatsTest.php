<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Registrations\Actions\FillFreedSeats;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Mail\RegistrationConfirmationMail;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->event = Event::factory()->published()->withMeetingPoints(1)->create([
        'title' => 'Chouf Cedars Ridge',
        'capacity' => 2,
        'difficulty_level' => DifficultyLevel::Foundation,
        'starts_on' => now()->addDays(10)->toDateString(),
    ]);
});

function fillUp(Event $event): void
{
    EventRegistration::factory()->count($event->capacity)->for($event)->create();
}

function waitlistPayload(Event $event, string $phone = '+961 71 000 001'): array
{
    return [
        'name' => 'Waiting Walker',
        'phone' => $phone,
        'email' => 'walker@example.com',
        'meeting_point_id' => $event->meetingPoints()->value('id'),
        'emergency_contact_name' => 'Someone',
        'emergency_contact_phone' => '+961 3 000 000',
        'terms' => '1',
    ];
}

describe('sold out badges', function () {
    it('marks sold-out and almost-full hikes on the cards', function () {
        fillUp($this->event);
        $almostFull = Event::factory()->published()->create(['title' => 'Almost Full Hike', 'capacity' => 10, 'starts_on' => now()->addDays(20)->toDateString()]);
        EventRegistration::factory()->count(7)->for($almostFull)->create();
        Event::factory()->published()->create(['title' => 'Roomy Hike', 'capacity' => 40, 'starts_on' => now()->addDays(30)->toDateString()]);

        $this->get(route('events.index'))
            ->assertSeeInOrder(['Sold out', 'Chouf Cedars Ridge', 'Only 3 seats left', 'Almost Full Hike', 'Roomy Hike']);
    });

    it('shows the badge on the homepage next-event card', function () {
        fillUp($this->event);

        $this->get(route('home'))->assertSee('Sold out')->assertSee('View Event');
    });

    it('does not badge past events', function () {
        $past = Event::factory()->published()->past()->create(['capacity' => 1]);
        EventRegistration::factory()->for($past)->create();
        $this->event->delete();

        $this->get(route('events.index'))->assertSee($past->title)->assertDontSee('Sold out');
    });
});

describe('waitlist', function () {
    it('lets visitors join the waitlist of a full hike', function () {
        fillUp($this->event);

        $this->get(route('events.show', $this->event))
            ->assertSee('Fully booked')
            ->assertSee('Join the Waitlist');
        $this->get(route('events.register', $this->event))
            ->assertSee('This hike is fully booked.')
            ->assertSee('Join the waitlist');

        $this->post(route('events.register.store', $this->event), waitlistPayload($this->event));
        $this->post(route('events.register.store', $this->event), waitlistPayload($this->event, '+961 71 000 002'));

        [$first, $second] = EventRegistration::where('status', RegistrationStatus::Waitlisted)->orderBy('id')->get()->all();
        expect($first->waitlistPosition())->toBe(1)->and($second->waitlistPosition())->toBe(2)
            ->and($this->event->fresh()->seatsLeft())->toBe(0);

        $this->get(route('registrations.show', $second))
            ->assertSee("You're on the waitlist")
            ->assertSee("You're number 2 on the waitlist", false);

        Mail::assertQueued(RegistrationConfirmationMail::class, fn ($mail) => str_starts_with($mail->envelope()->subject, "You're on the waitlist: Chouf Cedars Ridge"));
    });

    it('refuses a second waitlist entry with the same phone', function () {
        fillUp($this->event);
        EventRegistration::factory()->waitlisted()->for($this->event)->create(['phone' => '+96171000001']);

        $this->post(route('events.register.store', $this->event), waitlistPayload($this->event))
            ->assertSessionHas('error', 'This phone number is already registered for this hike. Contact us if you need to change your booking.');
    });

    it('moves waitlisted hikers up in order when the CMS raises the capacity', function () {
        fillUp($this->event);
        $first = EventRegistration::factory()->waitlisted()->for($this->event)->create(['email' => 'first@example.com']);
        $second = EventRegistration::factory()->waitlisted()->for($this->event)->create(['email' => 'second@example.com']);
        $third = EventRegistration::factory()->waitlisted()->for($this->event)->create();

        $this->event->update(['capacity' => 4]);
        $promoted = app(FillFreedSeats::class)->handle($this->event);

        expect($promoted)->toHaveCount(2)
            ->and($first->fresh()->status)->toBe(RegistrationStatus::Confirmed)
            ->and($second->fresh()->status)->toBe(RegistrationStatus::Confirmed)
            ->and($third->fresh()->status)->toBe(RegistrationStatus::Waitlisted)
            ->and($third->fresh()->waitlistPosition())->toBe(1);

        Mail::assertQueued(RegistrationConfirmationMail::class, fn ($mail) => $mail->promoted && $mail->hasTo('first@example.com')
            && str_starts_with($mail->envelope()->subject, 'A seat opened up'));
        Mail::assertQueued(RegistrationConfirmationMail::class, 2);
    });

    it('promotes from the CMS save and keeps X event hikers pending', function () {
        $this->event->update(['is_xtreme' => true]);
        fillUp($this->event);
        $waiting = EventRegistration::factory()->waitlisted()->for($this->event)->create();

        $this->actingAs(userWithRole(Role::Admin))->put(route('admin.events.update', $this->event), [
            ...$this->event->only(['title', 'slug', 'location']),
            'starts_on' => $this->event->starts_on->format('Y-m-d'),
            'distance_km' => '10', 'elevation_gain_m' => '200', 'elevation_loss_m' => '200',
            'fee' => '30', 'capacity' => '3', 'is_xtreme' => '1', 'waitlist_enabled' => '1',
        ])->assertSessionHasNoErrors();

        expect($waiting->fresh()->status)->toBe(RegistrationStatus::Pending);
    });

    it('does not promote anyone for past or cancelled hikes', function (array $state) {
        fillUp($this->event);
        $waiting = EventRegistration::factory()->waitlisted()->for($this->event)->create();
        $this->event->update(['capacity' => 5] + $state);

        expect(app(FillFreedSeats::class)->handle($this->event))->toBe([])
            ->and($waiting->fresh()->status)->toBe(RegistrationStatus::Waitlisted);
    })->with([
        'past' => [['starts_on' => now()->subDays(2)->toDateString()]],
        'cancelled' => [['status' => 'cancelled']],
    ]);

    it('shows the waitlist in the CMS', function () {
        fillUp($this->event);
        EventRegistration::factory()->waitlisted()->for($this->event)->create(['name' => 'Patient Person']);

        $this->actingAs(userWithRole(Role::Admin))->get(route('admin.events.edit', $this->event))
            ->assertSee('2 / 2 seats taken · 1 on the waitlist')
            ->assertSee('Patient Person')
            ->assertSee('On the waitlist')
            ->assertSee('#1 in line');
    });
});

describe('booking deadline', function () {
    it('closes at midnight Lebanon time before the hike day by default', function () {
        $this->event->update(['starts_on' => '2026-11-07']);

        expect($this->event->fresh()->registrationDeadline()->toIso8601String())->toBe('2026-11-06T22:00:00+00:00');

        $this->travelTo('2026-11-06 21:59:00');
        expect($this->event->fresh()->isOpenForRegistration())->toBeTrue();
        $this->get(route('events.show', $this->event))->assertSee('Bookings close Sat 7 Nov at 00:00 (Lebanon time).');

        $this->travelTo('2026-11-06 22:00:00');
        expect($this->event->fresh()->isOpenForRegistration())->toBeFalse();
        $this->get(route('events.show', $this->event))->assertSee('Registration closed');
    });

    it('follows the organizer\'s deadline', function () {
        $this->event->update(['registration_closes_at' => now()->subMinute()]);

        $this->get(route('events.register', $this->event))->assertSee('Registration for this hike is closed.');
        $this->post(route('events.register.store', $this->event), waitlistPayload($this->event))
            ->assertSessionHas('error', 'Registration for this hike is closed.');
    });

    it('does not offer the waitlist once bookings are closed', function () {
        fillUp($this->event);
        $this->event->update(['registration_closes_at' => now()->subMinute()]);

        $this->get(route('events.show', $this->event))->assertDontSee('Join the Waitlist');
        $this->get(route('events.index'))->assertDontSee('Sold out');
    });

    it('is set in the CMS in Lebanon time', function () {
        $admin = userWithRole(Role::Admin);
        $payload = [
            ...$this->event->only(['title', 'slug', 'location']),
            'starts_on' => '2026-11-07',
            'distance_km' => '10', 'elevation_gain_m' => '200', 'elevation_loss_m' => '200',
            'fee' => '30', 'capacity' => '20',
        ];

        $this->actingAs($admin)->put(route('admin.events.update', $this->event), $payload + ['registration_closes_at' => '2026-11-05T18:00'])
            ->assertSessionHasNoErrors();
        $event = $this->event->fresh();
        expect($event->registration_closes_at->toIso8601String())->toBe('2026-11-05T16:00:00+00:00')
            ->and($event->waitlist_enabled)->toBeFalse();

        $this->actingAs($admin)->get(route('admin.events.edit', $event))->assertSee('value="2026-11-05T18:00"', false);

        $this->actingAs($admin)->put(route('admin.events.update', $this->event), $payload + ['registration_closes_at' => '2026-11-08T08:00'])
            ->assertSessionHasErrors(['registration_closes_at' => 'Bookings must close on or before the day of the hike.']);
    });

    it('is not copied when duplicating', function () {
        $this->event->update(['registration_closes_at' => now()->addDay()]);

        $this->actingAs(userWithRole(Role::Admin))->post(route('admin.events.duplicate', $this->event));

        expect(Event::latest('id')->first()->registration_closes_at)->toBeNull();
    });
});
