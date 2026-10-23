<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Mail\RegistrationCancelledMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456']);
    $this->user->assignRole(Role::Member->value);
    $this->hiker = Hiker::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'user_id' => $this->user->id]);
});

function myBooking(Event $event, array $attributes = []): EventRegistration
{
    $hiker = test()->hiker;

    return EventRegistration::factory()->for($event)->create($attributes + ['hiker_id' => $hiker->id, 'phone' => $hiker->phone, 'name' => $hiker->name]);
}

it('shows rank, progress, stats and rewards', function () {
    $this->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 140, 'description' => 'Welcome back']);
    $past = Event::factory()->published()->past()->create(['title' => 'Qadisha Valley Descent', 'distance_km' => 14.5, 'difficulty_level' => DifficultyLevel::Endurance]);
    $hike = myBooking($past, ['attendance' => Attendance::Attended]);
    $hike->pointTransactions()->create(['hiker_id' => $this->hiker->id, 'type' => PointType::Hike, 'points' => 14, 'description' => 'Completed Qadisha']);
    $hike->pointTransactions()->create(['hiker_id' => Hiker::factory()->create()->id, 'type' => PointType::Referral, 'points' => 10, 'description' => 'Someone else']);
    myBooking(Event::factory()->published()->past()->create(['title' => 'Missed Hike']), ['attendance' => Attendance::NoShow]);

    $this->actingAs($this->user)->get(route('account'))
        ->assertOk()
        ->assertSee('Trekker · 154 points')
        ->assertSee('aria-valuenow="61"', false) // (154 - 80) / (200 - 80)
        ->assertSee('<strong>46 points</strong> to Mountaineer (200 points)', false)
        ->assertSeeInOrder(['Hikes completed', '1', 'Distance hiked', '14.5 km', 'Leaderboard', '#1'])
        ->assertSeeInOrder(['Hike history', 'Qadisha Valley Descent', '14.5 km', 'Level 2', '+14'])
        ->assertDontSee('Missed Hike')
        ->assertSeeInOrder(['Trekker', 'Unlocked', '50% off one hike', 'Mountaineer', 'Locked', 'One free Moderate'])
        ->assertSee('Welcome back');
});

it('shows the top rank as complete', function () {
    $this->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 900, 'description' => 'Legend']);

    $this->actingAs($this->user)->get(route('account'))
        ->assertSee('Elite · 900 points')
        ->assertSee("You've reached the highest rank.", false)
        ->assertSee('aria-valuenow="100"', false);
});

it('lists upcoming bookings with their payment state', function () {
    $paid = myBooking(Event::factory()->published()->create(['title' => 'Paid Hike', 'fee_cents' => 3000, 'starts_on' => now()->addDays(5)->toDateString()]), ['paid_at' => now()]);
    $unpaid = myBooking(Event::factory()->published()->create(['title' => 'Unpaid Hike', 'fee_cents' => 3000, 'starts_on' => now()->addDays(10)->toDateString()]));
    myBooking(Event::factory()->published()->create(['title' => 'Free Hike', 'fee_cents' => 0, 'starts_on' => now()->addDays(15)->toDateString()]));
    myBooking(Event::factory()->published()->create(['title' => 'Waiting Hike', 'starts_on' => now()->addDays(20)->toDateString()]), ['status' => RegistrationStatus::Waitlisted]);
    myBooking(Event::factory()->published()->create(['title' => 'Cancelled Hike']), ['status' => RegistrationStatus::Cancelled]);

    $this->actingAs($this->user)->get(route('account'))
        ->assertSeeInOrder(['Paid Hike', '✓ Paid', 'Unpaid Hike', '$30 to pay', 'Free Hike', 'Free hike', 'Waiting Hike', 'On the waitlist #1'])
        ->assertDontSee('Cancelled Hike')
        ->assertDontSee(route('account.bookings.cancel', $paid))
        ->assertSee(route('account.bookings.cancel', $unpaid))
        ->assertSee('To cancel a paid booking, message us on WhatsApp');
});

describe('cancelling own bookings', function () {
    it('cancels an unpaid booking and gives the seat to the waitlist', function () {
        $event = Event::factory()->published()->create(['title' => 'Full Hike', 'capacity' => 1]);
        $mine = myBooking($event, ['email' => 'rima@example.com']);
        $waiting = EventRegistration::factory()->for($event)->waitlisted()->create();

        $this->actingAs($this->user)->post(route('account.bookings.cancel', $mine))
            ->assertSessionHas('success', 'Your booking for Full Hike is cancelled.');

        expect($mine->fresh())->status->toBe(RegistrationStatus::Cancelled)->cancellation_reason->toBe('Cancelled by the hiker')
            ->and($waiting->fresh()->status)->toBe(RegistrationStatus::Confirmed);
        Mail::assertQueued(RegistrationCancelledMail::class, fn ($mail) => $mail->hasTo('rima@example.com'));
    });

    it('refuses paid bookings, closed hikes and other people\'s bookings', function () {
        $paid = myBooking(Event::factory()->published()->create(), ['paid_at' => now()]);
        $today = myBooking(Event::factory()->published()->create(['starts_on' => today()->toDateString()]));
        $someoneElse = EventRegistration::factory()->for(Event::factory()->published())->create();

        $this->actingAs($this->user)->post(route('account.bookings.cancel', $paid))
            ->assertSessionHas('error', 'This booking is paid: message us on WhatsApp to cancel it and arrange your refund.');
        $this->actingAs($this->user)->post(route('account.bookings.cancel', $today))
            ->assertSessionHas('error', 'Bookings for this hike are closed, so it can no longer be cancelled online. Message us on WhatsApp.');
        $this->actingAs($this->user)->post(route('account.bookings.cancel', $someoneElse))->assertForbidden();

        expect(EventRegistration::where('status', RegistrationStatus::Cancelled)->count())->toBe(0);
    });

    it('needs a login', function () {
        $booking = myBooking(Event::factory()->published()->create());

        $this->post(route('account.bookings.cancel', $booking))->assertRedirect(route('login'));
    });
});

it('fills in the booking form for logged-in members', function () {
    $event = Event::factory()->published()->create();

    $this->actingAs($this->user)->get(route('events.register', $event))
        ->assertSee('value="Rima Haddad"', false)
        ->assertSee('value="+96170123456"', false)
        ->assertSee('value="rima@example.com"', false);

    $this->post(route('logout'));
    $this->get(route('events.register', $event))->assertDontSee('value="Rima Haddad"', false);
});
