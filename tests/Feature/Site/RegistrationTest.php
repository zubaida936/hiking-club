<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Mail\RegistrationConfirmationMail;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->event = Event::factory()->published()->withMeetingPoints(2)->create([
        'title' => 'Horch Ehden Nature Reserve',
        'capacity' => 20,
        'difficulty_level' => DifficultyLevel::Endurance,
    ]);
});

function bookingPayload(Event $event, array $overrides = []): array
{
    return array_merge([
        'name' => '  Rima   Haddad ',
        'phone' => '+961 70-123 456',
        'email' => ' Rima@Example.com ',
        'meeting_point_id' => $event->meetingPoints->last()?->id,
        'emergency_contact_name' => 'Karim Haddad',
        'emergency_contact_phone' => '+961 3 987 654',
        'notes' => 'First hike with you!',
        'terms' => '1',
    ], $overrides);
}

describe('booking form', function () {
    it('is linked from the event page and shows pickup points and seats', function () {
        EventRegistration::factory()->count(3)->for($this->event)->create();

        $this->get(route('events.show', $this->event))
            ->assertSee('Reserve Your Spot')
            ->assertSee(route('events.register', $this->event))
            ->assertSee('17 of 20');

        $this->get(route('events.register', $this->event))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('17 seats left.')
            ->assertSee($this->event->meetingPoints->first()->name)
            ->assertSee($this->event->meetingPoints->first()->pickupTimeShort())
            ->assertSee('Emergency contact')
            ->assertSee(config('site.booking.terms'))
            ->assertDontSee('check you\'re ready', false);
    });

    it('asks level 3+ hikers to confirm they are ready', function () {
        $this->event->update(['difficulty_level' => DifficultyLevel::Advanced]);

        $this->get(route('events.show', $this->event))->assertSee('Register for Level 3');
        $this->get(route('events.register', $this->event))
            ->assertSee('Register for Level 3')
            ->assertSee(DifficultyLevel::Advanced->description())
            ->assertSee('completed a Level 2 hike')
            ->assertSee('name="level_acknowledged"', false);

        $this->post(route('events.register.store', $this->event), bookingPayload($this->event))
            ->assertSessionHasErrors('level_acknowledged');

        $this->post(route('events.register.store', $this->event), bookingPayload($this->event, ['level_acknowledged' => '1']))
            ->assertSessionHasNoErrors();
        expect(EventRegistration::sole()->level_acknowledged_at)->not->toBeNull();
    });

    it('returns 404 for drafts', function () {
        $draft = Event::factory()->create();

        $this->get(route('events.register', $draft))->assertNotFound();
        $this->post(route('events.register.store', $draft), [])->assertNotFound();
    });
});

describe('booking', function () {
    it('books a seat and shows the confirmation', function () {
        $response = $this->post(route('events.register.store', $this->event), bookingPayload($this->event));

        $registration = EventRegistration::sole();
        $response->assertRedirect(route('registrations.show', $registration));

        expect($registration)
            ->status->toBe(RegistrationStatus::Confirmed)
            ->name->toBe('Rima Haddad')
            ->phone->toBe('+96170123456')
            ->email->toBe('rima@example.com')
            ->pickup_point->toBe($this->event->meetingPoints->last()->name)
            ->pickup_time->toBe($this->event->meetingPoints->last()->pickup_time)
            ->emergency_contact_phone->toBe('+9613987654')
            ->notes->toBe('First hike with you!')
            ->terms_accepted_at->not->toBeNull()
            ->level_acknowledged_at->toBeNull()
            ->and($registration->reference)->toMatch('/^[A-HJ-NP-Z2-9]{8}$/');

        $this->get(route('registrations.show', $registration))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee($registration->reference)
            ->assertSee("You're booked!")
            ->assertSee('Horch Ehden Nature Reserve')
            ->assertSee($registration->pickup_point)
            ->assertSee('Karim Haddad')
            ->assertSee('rima@example.com')
            ->assertSee(config('site.booking.payment_note'))
            ->assertSee('wa.me/');

        $this->get(route('events.show', $this->event))->assertSee('19 of 20');
        Mail::assertQueued(RegistrationConfirmationMail::class, fn ($mail) => $mail->hasTo('rima@example.com'));
    });

    it('does not send an email when none was given', function () {
        $this->post(route('events.register.store', $this->event), bookingPayload($this->event, ['email' => '']))
            ->assertSessionHasNoErrors();

        expect(EventRegistration::sole()->email)->toBeNull();
        Mail::assertNothingQueued();
    });

    it('holds a pending seat on X events until an organizer approves', function () {
        $this->event->update(['is_xtreme' => true]);

        $this->post(route('events.register.store', $this->event), bookingPayload($this->event));

        $registration = EventRegistration::sole();
        expect($registration->status)->toBe(RegistrationStatus::Pending);
        $this->get(route('registrations.show', $registration))
            ->assertSee('Registration received')
            ->assertSee('An organizer will review your registration');
        $this->get(route('events.show', $this->event))->assertSee('19 of 20');
    });

    it('validates the form', function (array $overrides, string $field) {
        $this->post(route('events.register.store', $this->event), bookingPayload($this->event, $overrides))
            ->assertSessionHasErrors($field);

        expect(EventRegistration::count())->toBe(0);
    })->with([
        'no name' => [['name' => ''], 'name'],
        'bad phone' => [['phone' => 'call me'], 'phone'],
        'bad email' => [['email' => 'nope'], 'email'],
        'no pickup' => [['meeting_point_id' => ''], 'meeting_point_id'],
        'pickup of another event' => [['meeting_point_id' => 999999], 'meeting_point_id'],
        'no emergency contact' => [['emergency_contact_name' => ''], 'emergency_contact_name'],
        'emergency phone is own phone' => [['emergency_contact_phone' => '+961 70-123 456'], 'emergency_contact_phone'],
        'terms not accepted' => [['terms' => ''], 'terms'],
        'honeypot' => [['website' => 'http://spam.test'], 'website'],
    ]);

    it('does not need a pickup point when the event has none', function () {
        $this->event->meetingPoints()->delete();

        $this->post(route('events.register.store', $this->event), bookingPayload($this->event, ['meeting_point_id' => '']))
            ->assertSessionHasNoErrors();

        expect(EventRegistration::sole()->pickup_point)->toBeNull();
    });

    it('rejects a second booking with the same phone number', function () {
        $this->post(route('events.register.store', $this->event), bookingPayload($this->event));

        $this->from(route('events.register', $this->event))
            ->post(route('events.register.store', $this->event), bookingPayload($this->event, ['phone' => '+96170123456']))
            ->assertRedirect(route('events.register', $this->event))
            ->assertSessionHas('error', 'This phone number is already registered for this hike. Contact us if you need to change your booking.');

        expect(EventRegistration::count())->toBe(1);
    });

    it('lets a cancelled booking register again and frees its seat', function () {
        EventRegistration::factory()->cancelled()->for($this->event)->create(['phone' => '+96170123456']);

        $this->get(route('events.show', $this->event))->assertSee('20 of 20');
        $this->post(route('events.register.store', $this->event), bookingPayload($this->event))->assertSessionHasNoErrors();
        expect(EventRegistration::active()->count())->toBe(1);
    });
});

describe('sold out and closed', function () {
    it('stops taking bookings when the hike is full', function () {
        $this->event->update(['capacity' => 2]);
        EventRegistration::factory()->for($this->event)->create();
        EventRegistration::factory()->pending()->for($this->event)->create();

        $this->get(route('events.show', $this->event))
            ->assertSee('Fully booked')
            ->assertDontSee(route('events.register', $this->event));
        $this->get(route('events.register', $this->event))
            ->assertSee('Sorry, this hike is fully booked.')
            ->assertDontSee('Book my seat');

        $this->post(route('events.register.store', $this->event), bookingPayload($this->event))
            ->assertSessionHas('error', 'Sorry, this hike is fully booked.');
        expect(EventRegistration::count())->toBe(2);
    });

    it('closes registration on the day of the hike, for past and for cancelled hikes', function (Closure $state) {
        $state($this->event);

        $this->get(route('events.register', $this->event))->assertSee('Registration for this hike is closed.');
        $this->post(route('events.register.store', $this->event), bookingPayload($this->event))
            ->assertSessionHas('error', 'Registration for this hike is closed.');
        expect(EventRegistration::count())->toBe(0);
    })->with([
        'today' => fn (Event $e) => $e->update(['starts_on' => today()->toDateString()]),
        'past' => fn (Event $e) => $e->update(['starts_on' => today()->subDays(3)->toDateString()]),
        'cancelled' => fn (Event $e) => $e->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Storm']),
    ]);

    it('shows "Registration closed" on the event page on the day of the hike', function () {
        $this->event->update(['starts_on' => today()->toDateString()]);

        $this->get(route('events.show', $this->event))->assertSee('Registration closed');
    });
});

describe('confirmation', function () {
    it('returns 404 for an unknown booking link', function () {
        $this->get('/bookings/'.str_repeat('a', 64))->assertNotFound();
    });

    it('builds the confirmation email', function () {
        $registration = EventRegistration::factory()->for($this->event)->create(['reference' => 'K7F3Q2MZ', 'pickup_point' => 'Sour', 'pickup_time' => '05:50:00']);

        (new RegistrationConfirmationMail($registration))
            ->assertHasSubject("You're booked: Horch Ehden Nature Reserve · {$this->event->starts_on->format('D j M')} (ref K7F3Q2MZ)")
            ->assertSeeInHtml('K7F3Q2MZ')
            ->assertSeeInHtml('Sour at 05:50')
            ->assertSeeInHtml($this->event->formattedFee())
            ->assertSeeInHtml(route('registrations.show', $registration));
    });
});

describe('CMS', function () {
    it('lists the registrations on the event screen', function () {
        EventRegistration::factory()->for($this->event)->create(['name' => 'Rima Haddad', 'reference' => 'K7F3Q2MZ', 'notes' => 'Vegetarian']);
        EventRegistration::factory()->cancelled()->for($this->event)->create(['name' => 'Cancelled Person']);

        $this->actingAs(userWithRole(Role::Admin))
            ->get(route('admin.events.edit', $this->event))
            ->assertSee('Registrations')
            ->assertSee('1 / 20 seats taken')
            ->assertSee('Rima Haddad')
            ->assertSee('K7F3Q2MZ')
            ->assertSee('Vegetarian')
            ->assertSee('Cancelled Person');
    });
});
