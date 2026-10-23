<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Registrations\Actions\SendEventReminders;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Mail\EventReminderMail;
use App\Mail\EventUpdateMail;
use App\Mail\RegistrationCancelledMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventUpdate;
use App\Models\RewardRedemption;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->admin = userWithRole(Role::Admin);
    $this->event = Event::factory()->published()->withMeetingPoints(1)->create([
        'title' => 'Horch Ehden Nature Reserve',
        'starts_on' => '2026-11-07',
        'fee_cents' => 3000,
        'what_to_bring' => ['Hiking shoes', '2L water'],
    ]);
    $this->travelTo('2026-11-01 10:00');
});

describe('cancelling a hike', function () {
    it('cancels every booking, emails the hikers and flags refunds', function () {
        $paid = EventRegistration::factory()->for($this->event)->create(['email' => 'paid@example.com', 'paid_at' => now()]);
        $unpaid = EventRegistration::factory()->for($this->event)->create(['email' => 'unpaid@example.com']);
        $waiting = EventRegistration::factory()->for($this->event)->waitlisted()->create(['email' => 'wait@example.com']);
        $already = EventRegistration::factory()->for($this->event)->cancelled()->create(['email' => 'gone@example.com']);
        RewardRedemption::create(['hiker_id' => $unpaid->hiker_id, 'reward' => 'elite_free_hike', 'event_registration_id' => $unpaid->id]);

        $this->actingAs($this->admin)->post(route('admin.events.cancel', $this->event), ['cancellation_reason' => 'Storm warning'])
            ->assertSessionHas('success', 'Event cancelled. 3 bookings cancelled and the hikers emailed (paid ones are marked "Refund due").');

        foreach ([$paid, $unpaid, $waiting] as $registration) {
            expect($registration->fresh())->status->toBe(RegistrationStatus::Cancelled)
                ->cancellation_reason->toBe('The hike was cancelled: Storm warning');
        }
        expect($paid->fresh()->refundDue())->toBeTrue()
            ->and(RewardRedemption::sole()->released_at)->not->toBeNull();

        Mail::assertQueued(RegistrationCancelledMail::class, 3);
        Mail::assertNotQueued(RegistrationCancelledMail::class, fn ($mail) => $mail->hasTo('gone@example.com'));
        Mail::assertQueued(RegistrationCancelledMail::class, function ($mail) {
            $html = $mail->render();

            return $mail->hasTo('paid@example.com')
                && str_starts_with($mail->envelope()->subject, 'Hike cancelled: Horch Ehden Nature Reserve')
                && str_contains($html, 'This hike is cancelled') && str_contains($html, 'Storm warning') && str_contains($html, "We'll pay it back");
        });
    });
});

describe('updates to hikers', function () {
    it('emails an update to everyone booked and shows it on the hike and booking pages', function () {
        $booked = EventRegistration::factory()->for($this->event)->create(['email' => 'a@example.com']);
        EventRegistration::factory()->for($this->event)->waitlisted()->create(['email' => 'b@example.com']);
        EventRegistration::factory()->for($this->event)->create(['email' => null]);
        EventRegistration::factory()->for($this->event)->cancelled()->create(['email' => 'c@example.com']);

        $this->actingAs($this->admin)->post(route('admin.events.updates.store', $this->event), ['message' => "Rain expected: bring a jacket.\nPickup unchanged."])
            ->assertSessionHas('success', 'Update posted on the hike page and emailed to 2 hikers.');

        expect(EventUpdate::sole())->emailed_count->toBe(2)->created_by->toBe($this->admin->id);
        Mail::assertQueued(EventUpdateMail::class, 2);
        Mail::assertQueued(EventUpdateMail::class, fn ($mail) => $mail->hasTo('a@example.com')
            && $mail->envelope()->subject === 'Update: Horch Ehden Nature Reserve · Sat 7 Nov'
            && str_contains($mail->render(), 'Rain expected: bring a jacket.'));

        $this->actingAs($this->admin)->get(route('admin.events.registrations', $this->event))->assertSee('Updates sent')->assertSee('emailed to 2');
        $this->post(route('logout'));
        $this->get(route('events.show', $this->event))->assertSee('Updates from the organizers')->assertSee('Rain expected: bring a jacket.');
        $this->get(route('registrations.show', $booked))->assertSee('Rain expected: bring a jacket.');
    });

    it('is for staff who manage registrations, and needs a message', function () {
        $this->actingAs(userWithRole(Role::Guide))->post(route('admin.events.updates.store', $this->event), ['message' => 'x'])->assertForbidden();
        $this->actingAs($this->admin)->post(route('admin.events.updates.store', $this->event), ['message' => ''])->assertSessionHasErrors('message');
    });

    it('announces a new date automatically and re-arms the reminder', function () {
        $booking = EventRegistration::factory()->for($this->event)->create(['email' => 'a@example.com', 'reminder_sent_at' => now()]);

        $this->actingAs($this->admin)->put(route('admin.events.update', $this->event), [
            ...$this->event->only(['title', 'slug', 'location']),
            'starts_on' => '2026-11-14',
            'distance_km' => '10', 'elevation_gain_m' => '200', 'elevation_loss_m' => '200',
            'fee' => '30', 'capacity' => '30',
            'meeting_points' => [['name' => 'Beirut', 'pickup_time' => '07:00']],
        ])->assertSessionHasNoErrors();

        expect(EventUpdate::sole()->message)->toStartWith('New date: this hike moves from Saturday, 7 November 2026 to Saturday, 14 November 2026.')
            ->and($booking->fresh()->reminder_sent_at)->toBeNull();
        Mail::assertQueued(EventUpdateMail::class, 1);

        // Saving again without a date change sends nothing new.
        $this->actingAs($this->admin)->put(route('admin.events.update', $this->event), [
            ...$this->event->fresh()->only(['title', 'slug', 'location']),
            'starts_on' => '2026-11-14',
            'distance_km' => '10', 'elevation_gain_m' => '200', 'elevation_loss_m' => '200',
            'fee' => '30', 'capacity' => '30',
            'meeting_points' => [['name' => 'Beirut', 'pickup_time' => '07:00']],
        ]);
        expect(EventUpdate::count())->toBe(1);
    });
});

describe('reminders', function () {
    it('emails confirmed hikers the day before, from 17:00 Lebanon time, once', function () {
        $confirmed = EventRegistration::factory()->for($this->event)->create([
            'email' => 'go@example.com', 'pickup_point' => 'Beirut - Jnah', 'pickup_time' => '07:30:00',
        ]);
        EventRegistration::factory()->for($this->event)->waitlisted()->create(['email' => 'wait@example.com']);
        EventRegistration::factory()->for($this->event)->pending()->create(['email' => 'pending@example.com']);
        EventRegistration::factory()->for($this->event)->create(['email' => null]);
        $this->event->updates()->create(['message' => 'Bring a head torch.']);

        $reminders = app(SendEventReminders::class);

        // Friday 6 Nov, 16:59 in Beirut (UTC+2): too early.
        expect($reminders->handle(CarbonImmutable::parse('2026-11-06 14:59:00', 'UTC')))->toBe(0);
        // Friday 17:00: send.
        expect($reminders->handle(CarbonImmutable::parse('2026-11-06 15:00:00', 'UTC')))->toBe(1);
        // Next hour: already sent.
        expect($reminders->handle(CarbonImmutable::parse('2026-11-06 16:00:00', 'UTC')))->toBe(0);

        expect($confirmed->fresh()->reminder_sent_at)->not->toBeNull();
        Mail::assertQueued(EventReminderMail::class, 1);
        Mail::assertQueued(EventReminderMail::class, function ($mail) {
            $html = $mail->render();

            return $mail->hasTo('go@example.com')
                && $mail->envelope()->subject === 'Reminder: Horch Ehden Nature Reserve on Sat 7 Nov · pickup 07:30 Beirut - Jnah'
                && str_contains($html, 'Hiking shoes, 2L water')
                && str_contains($html, 'Payment still due:')
                && str_contains($html, 'Bring a head torch.');
        });
    });

    it('runs every hour through the scheduler', function () {
        $this->artisan('events:send-reminders')->expectsOutputToContain('Queued 0 reminder(s).')->assertSuccessful();
        $this->artisan('schedule:list')->expectsOutputToContain('events:send-reminders');
    });
});
