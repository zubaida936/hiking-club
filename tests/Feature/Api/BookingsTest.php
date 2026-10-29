<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Mail\RegistrationConfirmationMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Mail::fake();
    config(['payments.online_gateway' => null]);
    $this->event = Event::factory()->published()->withMeetingPoints(2)->create([
        'title' => 'Chouf Cedars Ridge',
        'starts_on' => now()->addDays(10)->toDateString(),
        'fee_cents' => 3000,
        'capacity' => 20,
        'difficulty_level' => DifficultyLevel::Endurance,
    ]);
});

/**
 * A member with a hiker profile and an app token.
 */
function bookingMember(int $points = 0): array
{
    $user = User::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com']);
    $user->assignRole(Role::Member->value);
    $hiker = Hiker::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'user_id' => $user->id]);
    if ($points > 0) {
        $hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => $points, 'description' => 'Test']);
    }

    return [$user, $hiker, $user->createToken('Phone')->plainTextToken];
}

function apiBook(Event $event, array $overrides = [], ?string $token = null): TestResponse
{
    $request = $token ? test()->withToken($token) : test();

    return $request->postJson(route('api.v1.events.bookings.store', $event), array_merge([
        'name' => 'Karim Saad',
        'phone' => '+961 71 222 333',
        'email' => 'karim@example.com',
        'meeting_point_id' => $event->meetingPoints()->value('id'),
        'emergency_contact_name' => 'Maya Saad',
        'emergency_contact_phone' => '+961 3 444 555',
        'payment_method' => 'whish',
        'terms' => true,
    ], $overrides));
}

describe('booking form', function () {
    it('gives guests the pickup points, payment methods and terms', function () {
        $point = $this->event->meetingPoints()->first();

        $this->getJson(route('api.v1.events.booking-form', $this->event))
            ->assertOk()
            ->assertJsonPath('data.open', true)
            ->assertJsonPath('data.waitlist', false)
            ->assertJsonPath('data.x_locked', false)
            ->assertJsonPath('data.fee', ['cents' => 3000, 'formatted' => '$30'])
            ->assertJsonPath('data.meeting_points.0', ['id' => $point->id, 'name' => $point->name, 'pickup_time' => $point->pickupTimeShort()])
            ->assertJsonPath('data.payment_methods.*.key', ['whish', 'omt', 'cash'])
            ->assertJsonPath('data.terms', config('site.booking.terms'))
            ->assertJsonPath('data.rewards', [])
            ->assertJsonPath('data.member', null);
    });

    it('fills in the member and offers their rewards for this hike', function () {
        [, , $token] = bookingMember(points: 250); // Mountaineer: one free Level 2 hike

        $this->withToken($token)->getJson(route('api.v1.events.booking-form', $this->event))
            ->assertJsonPath('data.member', ['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com'])
            ->assertJsonPath('data.rewards', [['key' => 'mountaineer_free_moderate', 'label' => 'One free Moderate (Level 2) hike', 'percent' => 100, 'fee_after' => 'Free']]);
    });

    it('locks X events for hikers who are not approved', function () {
        $x = Event::factory()->published()->create(['starts_on' => now()->addDays(5)->toDateString(), 'is_xtreme' => true]);
        [, $hiker, $token] = bookingMember();

        $this->getJson(route('api.v1.events.booking-form', $x))->assertJsonPath('data.x_locked', true);
        $this->withToken($token)->getJson(route('api.v1.events.booking-form', $x))->assertJsonPath('data.x_locked', true);

        $hiker->update(['x_approved_at' => now()]);
        $this->app['auth']->forgetGuards(); // the test app would keep the member loaded before the approval
        $this->withToken($token)->getJson(route('api.v1.events.booking-form', $x))->assertJsonPath('data.x_locked', false);
    });

    it('shows when a hike is full and only the waitlist is left', function () {
        $this->event->update(['capacity' => 1, 'waitlist_enabled' => true]);
        EventRegistration::factory()->for($this->event)->create();

        $this->getJson(route('api.v1.events.booking-form', $this->event))->assertJsonPath('data.open', true)->assertJsonPath('data.waitlist', true);

        $this->event->update(['waitlist_enabled' => false]);
        $this->getJson(route('api.v1.events.booking-form', $this->event))->assertJsonPath('data.open', false);
    });

    it('hides drafts', function () {
        $this->getJson(route('api.v1.events.booking-form', Event::factory()->create()))->assertNotFound();
    });
});

describe('booking', function () {
    it('books a guest and returns the booking with transfer instructions', function () {
        $response = apiBook($this->event)
            ->assertCreated()
            ->assertJsonPath('data.status', ['key' => 'confirmed', 'label' => 'Confirmed'])
            ->assertJsonPath('data.name', 'Karim Saad')
            ->assertJsonPath('data.phone', '+96171222333')
            ->assertJsonPath('data.event.slug', $this->event->slug)
            ->assertJsonPath('data.event.seats.left', 19)
            ->assertJsonPath('data.amount_due', ['cents' => 3000, 'formatted' => '$30'])
            ->assertJsonPath('data.payment.state', 'due')
            ->assertJsonPath('data.payment.method', ['key' => 'whish', 'label' => 'Whish Money transfer'])
            ->assertJsonPath('data.payment.instructions.type', 'transfer')
            ->assertJsonPath('data.payment.instructions.provider', 'Whish Money')
            ->assertJsonPath('data.payment.instructions.account_number', config('payments.methods.whish.account_number'))
            ->assertJsonPath('data.cancellation.can_cancel', false);

        $registration = EventRegistration::sole();
        $response->assertJsonPath('data.reference', $registration->reference)
            ->assertJsonPath('data.token', $registration->token)
            ->assertJsonPath('data.payment.instructions.note', $registration->reference)
            ->assertJsonPath('data.web_url', route('registrations.show', $registration));

        Mail::assertQueued(RegistrationConfirmationMail::class);

        $this->getJson(route('api.v1.bookings.show', $registration->token))->assertOk()->assertJsonPath('data.reference', $registration->reference);
    });

    it('uses the website validation messages', function () {
        apiBook($this->event, ['phone' => '123', 'terms' => false, 'meeting_point_id' => null, 'payment_method' => null])
            ->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', 'Enter a valid phone number, e.g. +961 70 123 456.')
            ->assertJsonPath('errors.terms.0', 'Please accept the terms to book your seat.')
            ->assertJsonPath('errors.meeting_point_id.0', 'Choose where we pick you up.')
            ->assertJsonPath('errors.payment_method.0', 'Choose how you will pay.');

        expect(EventRegistration::count())->toBe(0);
    });

    it('asks to confirm the level on Level 3+ hikes', function () {
        $this->event->update(['difficulty_level' => DifficultyLevel::Advanced]);

        apiBook($this->event)->assertJsonValidationErrors(['level_acknowledged' => 'Please confirm that you meet the level requirements for this hike.']);
        apiBook($this->event, ['level_acknowledged' => true])->assertCreated();
    });

    it('answers domain rules with a message', function () {
        apiBook($this->event)->assertCreated();

        apiBook($this->event)->assertUnprocessable()->assertJsonStructure(['message'])->assertJsonMissingPath('errors');

        $this->event->update(['capacity' => 1, 'waitlist_enabled' => false]);
        apiBook($this->event, ['phone' => '+96171999888'])->assertUnprocessable()->assertJsonStructure(['message']);
        expect(EventRegistration::count())->toBe(1);
    });

    it('puts the hiker on the waitlist when the hike is full', function () {
        $this->event->update(['capacity' => 1, 'waitlist_enabled' => true]);
        EventRegistration::factory()->for($this->event)->create();

        apiBook($this->event)
            ->assertCreated()
            ->assertJsonPath('data.status.key', 'waitlisted')
            ->assertJsonPath('data.waitlist_position', 1)
            ->assertJsonPath('data.payment.state', 'after_confirmation');
    });

    it('lets a logged-in member use a reward', function () {
        [, $hiker, $token] = bookingMember(points: 250);

        apiBook($this->event, ['name' => 'Rima Haddad', 'phone' => '+96170123456', 'reward' => 'mountaineer_free_moderate', 'payment_method' => null], $token)
            ->assertCreated()
            ->assertJsonPath('data.amount_due', ['cents' => 0, 'formatted' => 'Free'])
            ->assertJsonPath('data.reward_used', 'mountaineer_free_moderate')
            ->assertJsonPath('data.payment.state', 'none')
            ->assertJsonPath('data.cancellation.can_cancel', true);

        expect($hiker->rewardRedemptions()->count())->toBe(1);
    });

    it('refuses rewards for guests', function () {
        apiBook($this->event, ['reward' => 'mountaineer_free_moderate'])->assertUnprocessable()->assertJsonStructure(['message']);
    });

    it('lets only approved members book X events', function () {
        $x = Event::factory()->published()->withMeetingPoints(1)->create(['starts_on' => now()->addDays(5)->toDateString(), 'is_xtreme' => true, 'fee_cents' => 0, 'difficulty_level' => DifficultyLevel::Endurance]);
        [, $hiker, $token] = bookingMember();
        $member = ['name' => 'Rima Haddad', 'phone' => '+96170123456'];

        apiBook($x, $member)->assertUnprocessable();
        apiBook($x, $member, $token)->assertUnprocessable();

        $hiker->update(['x_approved_at' => now()]);
        apiBook($x, $member, $token)->assertCreated();
    });

    it('refuses deactivated members', function () {
        [$user, , $token] = bookingMember();
        $user->forceFill(['is_active' => false])->save();

        apiBook($this->event, [], $token)->assertUnauthorized();
        expect(EventRegistration::count())->toBe(0);
    });
});

describe('payment', function () {
    it('starts an online payment and gets confirmed', function () {
        config(['payments.online_gateway' => 'test']);
        $registration = EventRegistration::factory()->for($this->event)->create(['payment_method' => 'online']);

        $this->getJson(route('api.v1.bookings.show', $registration->token))->assertJsonPath('data.payment.instructions', ['type' => 'online']);

        $url = $this->postJson(route('api.v1.bookings.pay', $registration->token))->assertOk()->json('checkout_url');
        $payment = Payment::sole();
        expect($url)->toBe(route('payments.test.show', $payment->external_id));

        // The hiker pays on the provider page in the browser, which then comes back to the website's return page.
        $back = $this->post(route('payments.test.simulate', $payment->external_id), ['outcome' => 'pay'])->headers->get('Location');
        $this->get($back)->assertRedirect(route('registrations.show', $registration));

        $this->getJson(route('api.v1.bookings.show', $registration->token))
            ->assertJsonPath('data.payment.state', 'paid')
            ->assertJsonPath('data.payment.instructions', null);

        $this->postJson(route('api.v1.bookings.pay', $registration->token))->assertUnprocessable()->assertJsonPath('message', 'This booking is already paid. Thank you!');
    });

    it('refuses online payment when it is not offered', function () {
        $registration = EventRegistration::factory()->for($this->event)->create(['payment_method' => 'online']);

        $this->postJson(route('api.v1.bookings.pay', $registration->token))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Online payment is not available right now. Please choose another way to pay.');
    });

    it('records the transaction number of a transfer', function () {
        $registration = EventRegistration::factory()->for($this->event)->create(['payment_method' => 'omt']);

        $this->postJson(route('api.v1.bookings.transfer', $registration->token), ['payment_reference' => ' 1234567890 '])
            ->assertOk()
            ->assertJsonPath('data.payment.instructions.provider', 'OMT Pay')
            ->assertJsonPath('data.payment.instructions.reported_transaction_number', '1234567890');

        $this->postJson(route('api.v1.bookings.transfer', $registration->token), [])->assertJsonValidationErrors('payment_reference');

        $cash = EventRegistration::factory()->for($this->event)->create(['payment_method' => 'cash']);
        $this->postJson(route('api.v1.bookings.transfer', $cash->token), ['payment_reference' => '1'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'A transaction number is only needed for Whish Money or OMT Pay transfers.');
    });

    it('needs the secret token', function () {
        $this->getJson('/api/v1/bookings/not-a-token')->assertNotFound();
        $this->postJson('/api/v1/bookings/not-a-token/pay')->assertNotFound();
    });
});

describe('cancelling', function () {
    it('lets the member cancel their own booking', function () {
        [, $hiker, $token] = bookingMember();
        $registration = EventRegistration::factory()->for($this->event)->create(['hiker_id' => $hiker->id, 'phone' => $hiker->phone]);

        $this->withToken($token)->getJson(route('api.v1.bookings.show', $registration->token))
            ->assertJsonPath('data.cancellation.can_cancel', true)
            ->assertJsonPath('data.cancellation.until', $this->event->registrationDeadline()->toIso8601String());

        $this->withToken($token)->postJson(route('api.v1.bookings.cancel', $registration->token))
            ->assertOk()
            ->assertJsonPath('data.status.key', 'cancelled')
            ->assertJsonPath('data.cancellation.reason', 'Cancelled by the hiker')
            ->assertJsonPath('data.cancellation.can_cancel', false);

        expect($registration->fresh()->status)->toBe(RegistrationStatus::Cancelled);
    });

    it('refuses guests and other members', function () {
        [, , $token] = bookingMember();
        $registration = EventRegistration::factory()->for($this->event)->create();

        $this->postJson(route('api.v1.bookings.cancel', $registration->token))->assertUnauthorized();
        $this->withToken($token)->postJson(route('api.v1.bookings.cancel', $registration->token))->assertForbidden();
        $this->withToken($token)->getJson(route('api.v1.bookings.show', $registration->token))->assertJsonPath('data.cancellation.can_cancel', false);

        expect($registration->fresh()->status)->toBe(RegistrationStatus::Confirmed);
    });

    it('refuses once the paid cancellation deadline passed', function () {
        [, $hiker, $token] = bookingMember();
        $this->event->update(['starts_on' => now()->addDay()->toDateString()]);
        $registration = EventRegistration::factory()->for($this->event)->create(['hiker_id' => $hiker->id, 'phone' => $hiker->phone, 'paid_at' => now()]);

        $this->withToken($token)->postJson(route('api.v1.bookings.cancel', $registration->token))
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);
    });
});
