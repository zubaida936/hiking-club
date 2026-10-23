<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Payments\Actions\ConfirmPayment;
use App\Domain\Payments\Actions\StartOnlinePayment;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Gateways\TestGateway;
use App\Mail\RegistrationConfirmationMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    config([
        'payments.methods.whish.account_name' => 'Hiking Club SAL',
        'payments.methods.whish.account_number' => '+961 71 222 333',
        'payments.online_gateway' => null,
    ]);
    $this->event = Event::factory()->published()->withMeetingPoints(1)->create([
        'title' => 'Chouf Cedars Ridge', 'fee_cents' => 3500, 'difficulty_level' => DifficultyLevel::Endurance,
    ]);
});

function bookWith(Event $event, string $method, string $phone = '+961 70 123 456'): EventRegistration
{
    test()->post(route('events.register.store', $event), [
        'name' => 'Rima Haddad',
        'phone' => $phone,
        'email' => 'rima@example.com',
        'meeting_point_id' => $event->meetingPoints()->value('id'),
        'emergency_contact_name' => 'Karim',
        'emergency_contact_phone' => '+961 3 000 000',
        'terms' => '1',
        'level_acknowledged' => '1',
        'payment_method' => $method,
    ])->assertSessionHasNoErrors();

    return EventRegistration::latest('id')->firstOrFail();
}

describe('choosing how to pay', function () {
    it('offers Whish, OMT and cash on paid hikes, and online only when a gateway is set', function () {
        $this->get(route('events.register', $this->event))
            ->assertSee('How will you pay $35?')
            ->assertSee('Whish Money transfer')
            ->assertSee('OMT Pay transfer')
            ->assertSee('Cash on the day')
            ->assertDontSee('Online payment');

        config(['payments.online_gateway' => 'test']);
        $this->get(route('events.register', $this->event))->assertSee('Online payment');
    });

    it('requires a choice on paid hikes but not on free ones', function () {
        $this->post(route('events.register.store', $this->event), ['payment_method' => ''])
            ->assertSessionHasErrors(['payment_method' => 'Choose how you will pay.']);
        $this->post(route('events.register.store', $this->event), ['payment_method' => 'online'])
            ->assertSessionHasErrors('payment_method'); // online is off

        $this->event->update(['fee_cents' => 0]);
        $this->get(route('events.register', $this->event))->assertDontSee('How will you pay');
        expect(bookWith($this->event, 'cash')->payment_method)->toBeNull();
    });
});

describe('Whish Money / OMT transfer', function () {
    it('shows the club account and lets the hiker send the transaction number', function () {
        $registration = bookWith($this->event, 'whish');

        expect($registration->payment_method)->toBe(PaymentMethod::Whish);
        $this->get(route('registrations.show', $registration))
            ->assertSee('Payment · $35')
            ->assertSee('Hiking Club SAL')
            ->assertSee('+961 71 222 333')
            ->assertSee("Write your booking reference <strong>{$registration->reference}</strong>", false);

        $this->post(route('registrations.transfer', $registration), ['payment_reference' => ' 9988776655 '])
            ->assertSessionHas('status', "Thanks! We'll confirm your payment soon.");
        expect($registration->fresh()->payment_reference)->toBe('9988776655');
        $this->get(route('registrations.show', $registration))->assertSee('We received transaction number <strong>9988776655</strong>', false);

        Mail::assertQueued(RegistrationConfirmationMail::class, fn ($mail) => $mail->render() !== ''
            && str_contains($mail->render(), '+961 71 222 333'));
    });

    it('shows the method and transaction number to organizers, who mark it paid', function () {
        $registration = bookWith($this->event, 'omt');
        $this->post(route('registrations.transfer', $registration), ['payment_reference' => 'OMT-123']);
        $admin = userWithRole(Role::Admin);

        $this->actingAs($admin)->get(route('admin.events.registrations', $this->event))
            ->assertSee('OMT Pay transfer')
            ->assertSee('#OMT-123');

        $this->actingAs($admin)->post(route('admin.registrations.payment', $registration), ['paid' => '1']);
        $this->post(route('logout'));
        $this->get(route('registrations.show', $registration))->assertSee('✓ Paid (OMT Pay transfer). Thank you!');

        // Once paid, the number can't be changed any more.
        $this->post(route('registrations.transfer', $registration), ['payment_reference' => 'X'])
            ->assertSessionHas('error', 'This booking is already paid. Thank you!');
    });

    it('only takes transaction numbers for wallet transfers', function () {
        $registration = bookWith($this->event, 'cash');

        $this->get(route('registrations.show', $registration))->assertSee('in cash to the organizers at the pickup point');
        $this->post(route('registrations.transfer', $registration), ['payment_reference' => '123'])
            ->assertSessionHas('error', 'A transaction number is only needed for Whish Money or OMT Pay transfers.');
    });
});

describe('online payment (test gateway)', function () {
    beforeEach(fn () => config(['payments.online_gateway' => 'test']));

    it('pays online and marks the booking paid', function () {
        $registration = bookWith($this->event, 'online');
        $this->get(route('registrations.show', $registration))->assertSee('Pay $35 now');

        $checkout = $this->post(route('registrations.pay', $registration))->assertRedirect()->headers->get('Location');
        $payment = Payment::sole();
        expect($checkout)->toBe(route('payments.test.show', $payment->external_id))
            ->and($payment)->status->toBe(PaymentStatus::Pending)->amount_cents->toBe(3500)->currency->toBe('USD');

        $this->get($checkout)->assertOk()->assertSee('$35 USD')->assertSee('test gateway');
        $back = $this->post(route('payments.test.simulate', $payment->external_id), ['outcome' => 'pay'])
            ->assertRedirect(route('payments.return', $payment->external_id))->headers->get('Location');

        $this->get($back)->assertRedirect(route('registrations.show', $registration))->assertSessionHas('status', 'Payment received. Thank you!');

        expect($payment->fresh())->status->toBe(PaymentStatus::Succeeded)->paid_at->not->toBeNull()
            ->and($registration->fresh())->paid_at->not->toBeNull()->payment_reference->toStartWith('TEST-');
        $this->get(route('registrations.show', $registration))->assertSee('✓ Paid (Online payment)', false);
    });

    it('lets the hiker try again after a failed payment', function () {
        $registration = bookWith($this->event, 'online');

        $this->post(route('registrations.pay', $registration));
        $first = Payment::sole();
        $this->post(route('payments.test.simulate', $first->external_id), ['outcome' => 'fail']);
        $this->get(route('payments.return', $first->external_id))
            ->assertSessionHas('error', 'The payment did not go through. You can try again or choose another way to pay.');

        expect($first->fresh()->status)->toBe(PaymentStatus::Failed)
            ->and($registration->fresh()->paid_at)->toBeNull();

        $this->post(route('registrations.pay', $registration))->assertRedirect();
        expect(Payment::count())->toBe(2);
    });

    it('confirms through the webhook, but only with the status from the gateway', function () {
        $registration = bookWith($this->event, 'online');
        $this->post(route('registrations.pay', $registration));
        $payment = Payment::sole();

        // A forged notification claiming success changes nothing.
        $this->post(route('payments.webhook', 'test'), ['external_id' => $payment->external_id, 'status' => 'succeeded'])->assertOk();
        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);

        app(TestGateway::class)->simulate($payment, PaymentStatus::Succeeded);
        $this->post(route('payments.webhook', 'test'), ['externalId' => $payment->external_id])->assertOk();
        expect($registration->fresh()->paid_at)->not->toBeNull();

        // Running it again (return page after the webhook) is harmless.
        expect(app(ConfirmPayment::class)->handle($payment->fresh()))->toBe(PaymentStatus::Succeeded);
        $this->post(route('payments.webhook', 'test'), ['external_id' => 'unknown'])->assertOk();
    });

    it('waits for the seat to be confirmed and refuses paid bookings', function () {
        $this->event->update(['is_xtreme' => true]);
        $pending = bookWith($this->event, 'online');

        $this->get(route('registrations.show', $pending))->assertSee("You'll pay once your seat is confirmed.", false);
        $this->post(route('registrations.pay', $pending))
            ->assertSessionHas('error', 'This booking can be paid once your seat is confirmed.');

        $pending->update(['status' => 'confirmed', 'paid_at' => now()]);
        $this->post(route('registrations.pay', $pending))->assertSessionHas('error', 'This booking is already paid. Thank you!');
        expect(Payment::count())->toBe(0);
    });

    it('never runs the test gateway in production', function () {
        $registration = bookWith($this->event, 'online');
        $this->post(route('registrations.pay', $registration));
        $payment = Payment::sole();

        app()->detectEnvironment(fn () => 'production');

        expect(PaymentMethod::Online->isAvailable())->toBeFalse();
        $this->get(route('payments.test.show', $payment->external_id))->assertNotFound();
        // (Called directly: outside the testing environment the request would also need a CSRF token.)
        expect(fn () => app(StartOnlinePayment::class)->handle($registration))
            ->toThrow(PaymentException::class, 'Online payment is not available right now. Please choose another way to pay.');
    });
});

it('counts online-paid hikes for points once attended', function () {
    config(['payments.online_gateway' => 'test']);
    $registration = bookWith($this->event, 'online');
    $this->post(route('registrations.pay', $registration));
    $payment = Payment::sole();
    app(TestGateway::class)->simulate($payment, PaymentStatus::Succeeded);
    $this->get(route('payments.return', $payment->external_id));

    $this->travelTo($this->event->starts_on->copy()->setTime(18, 0));
    $this->actingAs(userWithRole(Role::Admin))->post(route('admin.registrations.attendance', $registration), ['attendance' => 'attended']);

    expect($registration->hiker->totalPoints())->toBeGreaterThanOrEqual(14);
});
