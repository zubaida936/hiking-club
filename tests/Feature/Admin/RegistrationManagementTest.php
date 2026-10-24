<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Registrations\Events\AttendanceMarked;
use App\Mail\RegistrationCancelledMail;
use App\Mail\RegistrationConfirmationMail;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\Event as EventBus;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->admin = userWithRole(Role::Admin);
    $this->event = Event::factory()->published()->withMeetingPoints(2)->create([
        'title' => 'Horch Ehden Nature Reserve',
        'capacity' => 3,
        'fee_cents' => 3000,
        'starts_on' => now()->addDays(10)->toDateString(),
    ]);
});

function booking(Event $event, array $attributes = [], ?string $state = null): EventRegistration
{
    $factory = EventRegistration::factory()->for($event);

    return ($state ? $factory->{$state}() : $factory)->create($attributes);
}

describe('access', function () {
    it('is open to admins and guides but not to editors', function () {
        $this->actingAs($this->admin)->get(route('admin.registrations.index'))->assertOk()->assertSee('Horch Ehden Nature Reserve');
        $this->actingAs(userWithRole(Role::Guide))->get(route('admin.events.registrations', $this->event))->assertOk();
        $this->actingAs(userWithRole(Role::Editor))->get(route('admin.registrations.index'))->assertForbidden();
        $this->actingAs(userWithRole(Role::Editor))->get(route('admin.events.registrations.export', $this->event))->assertForbidden();
    });

    it('lets guides mark attendance but not change bookings', function () {
        $guide = userWithRole(Role::Guide);
        $registration = booking($this->event);

        $this->actingAs($guide)->get(route('admin.events.registrations', $this->event))
            ->assertSee($registration->name)
            ->assertDontSee(route('admin.registrations.edit', $registration));
        $this->actingAs($guide)->post(route('admin.registrations.cancel', $registration))->assertForbidden();
        $this->actingAs($guide)->post(route('admin.registrations.payment', $registration), ['paid' => 1])->assertForbidden();
        $this->actingAs($guide)->get(route('admin.registrations.edit', $registration))->assertForbidden();

        $this->travelTo($this->event->starts_on->copy()->setTime(9, 0));
        $this->actingAs($guide)->post(route('admin.registrations.attendance', $registration), ['attendance' => 'attended'])
            ->assertSessionHas('success');
        expect($registration->fresh()->attendance)->toBe(Attendance::Attended);
    });
});

describe('overview and list', function () {
    it('shows booking numbers per event', function () {
        booking($this->event, ['paid_at' => now()]);
        booking($this->event, state: 'pending');
        booking($this->event);
        booking($this->event, state: 'waitlisted');
        booking($this->event, state: 'cancelled');
        Event::factory()->published()->past()->create(['title' => 'Old Hike']);

        $this->actingAs($this->admin)->get(route('admin.registrations.index'))
            ->assertSeeInOrder(['Horch Ehden Nature Reserve', '3 / 3', '1', '1', '1 / 3'])
            ->assertDontSee('Old Hike');
        $this->actingAs($this->admin)->get(route('admin.registrations.index', ['when' => 'past']))
            ->assertSee('Old Hike')->assertDontSee('Horch Ehden Nature Reserve');
    });

    it('lists bookings in booking order, without cancelled ones unless asked', function () {
        $first = booking($this->event, ['name' => 'First Hiker']);
        booking($this->event, ['name' => 'Second Hiker', 'notes' => 'Vegetarian']);
        booking($this->event, ['name' => 'Gone Hiker', 'cancellation_reason' => 'Changed plans'], 'cancelled');

        // (The hidden WhatsApp list also names every seat holder, so rows are checked by their edit link.)
        $this->actingAs($this->admin)->get(route('admin.events.registrations', $this->event))
            ->assertSeeInOrder(['First Hiker', 'Second Hiker'])
            ->assertSee('Vegetarian')
            ->assertDontSee('Gone Hiker');

        $this->actingAs($this->admin)->get(route('admin.events.registrations', [$this->event, 'status' => 'cancelled']))
            ->assertSee('Gone Hiker')
            ->assertSee('Changed plans')
            ->assertDontSee(route('admin.registrations.edit', $first));
    });

    it('filters by pickup point and finds hikers by name, phone or reference', function () {
        $rima = booking($this->event, ['name' => 'Rima Haddad', 'phone' => '+96170123456', 'reference' => 'K7F3Q2MZ', 'pickup_point' => 'Sour', 'pickup_time' => '05:50:00']);
        // His phone contains "732", the digits of Rima's reference: a reference search must not match it.
        $karim = booking($this->event, ['name' => 'Karim Saad', 'phone' => '+96171732000', 'pickup_point' => 'Beirut - Jnah', 'pickup_time' => '07:30:00']);

        $this->actingAs($this->admin)->get(route('admin.events.registrations', $this->event))
            ->assertSeeInOrder(['Sour 05:50', '1', 'Beirut - Jnah 07:30', '1']);

        foreach ([['pickup' => 'Sour'], ['q' => 'rima'], ['q' => '70 123'], ['q' => 'k7f3q2mz']] as $filter) {
            $this->actingAs($this->admin)->get(route('admin.events.registrations', [$this->event, ...$filter]))
                ->assertSee(route('admin.registrations.edit', $rima))
                ->assertDontSee(route('admin.registrations.edit', $karim));
        }
    });

    it('builds a WhatsApp list of seat holders by pickup point', function () {
        booking($this->event, ['name' => 'Zed Late', 'phone' => '+96170000002', 'pickup_point' => 'Beirut - Jnah', 'pickup_time' => '07:30:00']);
        booking($this->event, ['name' => 'Rima Haddad', 'phone' => '+96170000001', 'pickup_point' => 'Sour', 'pickup_time' => '05:50:00']);
        booking($this->event, ['name' => 'Xena Pending', 'phone' => '+96170000003', 'pickup_point' => 'Sour', 'pickup_time' => '05:50:00'], 'pending');
        booking($this->event, ['name' => 'Wait Listed'], 'waitlisted');

        $expected = "*Horch Ehden Nature Reserve*\n{$this->event->starts_on->format('D j M Y')}\n\n📍 *Sour* 05:50\n1. Rima Haddad +96170000001\n2. Xena Pending +96170000003 (awaiting approval)\n\n📍 *Beirut - Jnah* 07:30\n1. Zed Late +96170000002\n\nTotal: 3";

        $this->actingAs($this->admin)->get(route('admin.events.registrations', $this->event))
            ->assertSee(e($expected), false)
            ->assertDontSee('Wait Listed +');
    });
});

describe('CSV export', function () {
    it('downloads the filtered list as CSV', function () {
        booking($this->event, ['name' => '=HYPERLINK("http://evil")', 'phone' => '+96170123456', 'reference' => 'K7F3Q2MZ', 'paid_at' => now()]);
        booking($this->event, ['name' => 'Gone'], 'cancelled');

        $response = $this->actingAs($this->admin)->get(route('admin.events.registrations.export', $this->event));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertDownload($this->event->starts_on->format('Y-m-d').'-'.$this->event->slug.'-registrations.csv');

        $csv = $response->streamedContent();
        $rows = array_map(str_getcsv(...), explode("\n", trim(substr($csv, 3))));

        expect(substr($csv, 0, 3))->toBe("\xEF\xBB\xBF")
            ->and($rows)->toHaveCount(2)
            ->and($rows[0][0])->toBe('Reference')
            ->and($rows[1][0])->toBe('K7F3Q2MZ')
            ->and($rows[1][2])->toBe("'=HYPERLINK(\"http://evil\")")
            ->and($rows[1][3])->toBe("'+96170123456")
            ->and($rows[1][10])->toBe('Yes');
    });
});

describe('actions', function () {
    it('approves a booking awaiting approval and tells the hiker', function () {
        $registration = booking($this->event, ['email' => 'x@example.com'], 'pending');

        $this->actingAs($this->admin)->post(route('admin.registrations.approve', $registration))->assertSessionHas('success');

        expect($registration->fresh())->status->toBe(RegistrationStatus::Confirmed)->updated_by->toBe($this->admin->id);
        Mail::assertQueued(RegistrationConfirmationMail::class, fn ($mail) => $mail->hasTo('x@example.com')
            && str_starts_with($mail->envelope()->subject, "You're booked"));

        $this->actingAs($this->admin)->post(route('admin.registrations.approve', $registration))
            ->assertSessionHas('error', 'Only bookings awaiting approval can be approved.');
    });

    it('cancels a booking, emails the hiker and gives the seat to the waitlist', function () {
        $registration = booking($this->event, ['email' => 'gone@example.com']);
        booking($this->event);
        booking($this->event);
        $waiting = booking($this->event, ['email' => 'next@example.com'], 'waitlisted');

        $this->actingAs($this->admin)->post(route('admin.registrations.cancel', $registration), [
            'cancellation_reason' => 'Asked to cancel on WhatsApp',
            'notify' => '1',
        ])->assertSessionHas('success');

        expect($registration->fresh())
            ->status->toBe(RegistrationStatus::Cancelled)
            ->cancellation_reason->toBe('Asked to cancel on WhatsApp')
            ->cancelled_at->not->toBeNull()
            ->and($waiting->fresh()->status)->toBe(RegistrationStatus::Confirmed);

        Mail::assertQueued(RegistrationCancelledMail::class, fn ($mail) => $mail->hasTo('gone@example.com'));
        Mail::assertQueued(RegistrationConfirmationMail::class, fn ($mail) => $mail->promoted && $mail->hasTo('next@example.com'));

        $this->actingAs($this->admin)->post(route('admin.registrations.cancel', $registration))
            ->assertSessionHas('error', 'This booking is already cancelled.');
    });

    it('can cancel without emailing the hiker', function () {
        $registration = booking($this->event, ['email' => 'quiet@example.com']);

        $this->actingAs($this->admin)->post(route('admin.registrations.cancel', $registration), ['notify' => '0']);

        expect($registration->fresh()->status)->toBe(RegistrationStatus::Cancelled);
        Mail::assertNotQueued(RegistrationCancelledMail::class);
    });

    it('builds the cancellation email', function () {
        $registration = booking($this->event, ['reference' => 'K7F3Q2MZ', 'cancellation_reason' => 'Bus is full'], 'cancelled');

        (new RegistrationCancelledMail($registration))
            ->assertHasSubject("Booking cancelled: Horch Ehden Nature Reserve · {$this->event->starts_on->format('D j M')} (ref K7F3Q2MZ)")
            ->assertSeeInHtml('Bus is full');
    });

    it('marks bookings paid and unpaid', function () {
        $registration = booking($this->event);

        $this->actingAs($this->admin)->post(route('admin.registrations.payment', $registration), ['paid' => '1']);
        expect($registration->fresh()->paid_at)->not->toBeNull();
        $this->actingAs($this->admin)->get(route('admin.events.registrations', $this->event))->assertSee('✓ Paid')->assertSee('Mark unpaid');

        $this->actingAs($this->admin)->post(route('admin.registrations.payment', $registration), ['paid' => '0']);
        expect($registration->fresh()->paid_at)->toBeNull();

        $registration->update(['status' => RegistrationStatus::Cancelled]);
        $this->actingAs($this->admin)->post(route('admin.registrations.payment', $registration), ['paid' => '1'])
            ->assertSessionHas('error', 'This booking is cancelled and can no longer be changed.');
    });

    it('edits booking details and copies the new pickup point', function () {
        $registration = booking($this->event, ['pickup_point' => 'Old stop', 'pickup_time' => '06:00:00']);
        $point = $this->event->meetingPoints()->orderByDesc('position')->first();

        $this->actingAs($this->admin)->get(route('admin.registrations.edit', $registration))->assertOk()->assertSee('Keep: Old stop 06:00');

        $this->actingAs($this->admin)->put(route('admin.registrations.update', $registration), [
            'name' => 'Rima  Haddad',
            'phone' => '+961 70 999 888',
            'email' => '',
            'meeting_point_id' => $point->id,
            'emergency_contact_name' => 'Karim',
            'emergency_contact_phone' => '+961 3 111 222',
            'notes' => 'Moved to another stop',
        ])->assertRedirect(route('admin.events.registrations', $this->event));

        expect($registration->fresh())
            ->name->toBe('Rima Haddad')
            ->phone->toBe('+96170999888')
            ->email->toBeNull()
            ->pickup_point->toBe($point->name)
            ->pickup_time->toBe($point->pickup_time)
            ->notes->toBe('Moved to another stop');
    });

    it('refuses a phone number already booked on the same hike', function () {
        booking($this->event, ['phone' => '+96170999888']);
        $registration = booking($this->event);

        $this->actingAs($this->admin)->put(route('admin.registrations.update', $registration), [
            'name' => 'X', 'phone' => '+961 70 999 888',
            'emergency_contact_name' => 'Y', 'emergency_contact_phone' => '+961 3 111 222',
        ])->assertSessionHas('error', 'Another booking for this hike already uses this phone number.');
    });
});

describe('attendance', function () {
    it('can only be marked from the hike day, for confirmed bookings', function () {
        $registration = booking($this->event);
        $pending = booking($this->event, state: 'pending');

        $this->actingAs($this->admin)->post(route('admin.registrations.attendance', $registration), ['attendance' => 'attended'])
            ->assertSessionHas('error', 'Attendance can be marked from the day of the hike.');
        $this->actingAs($this->admin)->get(route('admin.events.registrations', $this->event))->assertSee('Attendance can be marked from the hike day.');

        $this->travelTo($this->event->starts_on->copy()->setTime(5, 0));
        $this->actingAs($this->admin)->post(route('admin.registrations.attendance', $pending), ['attendance' => 'attended'])
            ->assertSessionHas('error', 'Only confirmed bookings can be marked as attended or no-show.');
    });

    it('records attended and no-show and tells the points engine', function () {
        EventBus::fake([AttendanceMarked::class]);
        $registration = booking($this->event);
        $this->travelTo($this->event->starts_on->copy()->addDay());

        $this->actingAs($this->admin)->post(route('admin.registrations.attendance', $registration), ['attendance' => 'attended']);
        expect($registration->fresh())->attendance->toBe(Attendance::Attended)->attendance_marked_at->not->toBeNull();
        EventBus::assertDispatched(AttendanceMarked::class, fn ($e) => $e->attendance === Attendance::Attended && $e->previous === null);

        $this->actingAs($this->admin)->post(route('admin.registrations.attendance', $registration), ['attendance' => 'no_show']);
        EventBus::assertDispatched(AttendanceMarked::class, fn ($e) => $e->attendance === Attendance::NoShow && $e->previous === Attendance::Attended);

        $this->actingAs($this->admin)->post(route('admin.registrations.attendance', $registration), ['attendance' => '']);
        expect($registration->fresh()->attendance)->toBeNull();
        EventBus::assertDispatchedTimes(AttendanceMarked::class, 3);

        $this->actingAs($this->admin)->get(route('admin.registrations.index', ['when' => 'past']))->assertSee('Horch Ehden Nature Reserve');
    });
});
