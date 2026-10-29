<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Members\Actions\DeleteAccount;
use App\Domain\Members\Exceptions\MemberException;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Shop\Enums\Fulfilment as ShopFulfilment;
use App\Domain\Shop\Enums\OrderStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\Order;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\XApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'password' => 'secret-pass']);
    $this->user->assignRole(Role::Member->value);
    $this->hiker = Hiker::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'user_id' => $this->user->id]);
});

function pastBooking(Hiker $hiker): EventRegistration
{
    return EventRegistration::factory()->for(Event::factory()->published()->past())->create([
        'hiker_id' => $hiker->id, 'name' => $hiker->name, 'phone' => $hiker->phone, 'email' => $hiker->email,
        'emergency_contact_name' => 'Karim Haddad', 'emergency_contact_phone' => '+9613111222', 'notes' => 'Vegetarian',
        'attendance' => Attendance::Attended, 'paid_at' => now()->subMonth(), 'payment_reference' => 'TX-1',
    ]);
}

function shopOrder(Hiker $hiker, OrderStatus $status = OrderStatus::Completed): Order
{
    return Order::create([
        'reference' => strtoupper(Str::random(8)), 'token' => Str::random(64), 'status' => $status, 'hiker_id' => $hiker->id,
        'name' => $hiker->name, 'phone' => $hiker->phone, 'email' => $hiker->email, 'fulfilment' => ShopFulfilment::Delivery,
        'delivery_city' => 'Beirut', 'delivery_address' => 'Hamra Street 12', 'notes' => 'Ring twice',
        'subtotal_cents' => 2000, 'delivery_fee_cents' => 400, 'total_cents' => 2400,
    ]);
}

describe('deleting', function () {
    it('anonymises bookings and orders and deletes everything personal', function () {
        $booking = pastBooking($this->hiker);
        $order = shopOrder($this->hiker);
        $this->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 40, 'description' => 'Welcome']);
        $this->hiker->rewardRedemptions()->create(['reward' => 'trekker_half_off', 'event_registration_id' => $booking->id, 'discount_cents' => 1500]);
        XApplication::factory()->for($this->hiker)->create();
        Subscriber::factory()->create(['email' => 'rima@example.com']);
        Subscriber::factory()->create(['email' => null, 'phone' => '+96170123456']);
        $this->user->createToken('Phone');
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $this->user->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'x', 'payload' => '', 'last_activity' => time()]);

        app(DeleteAccount::class)->handle($this->user, 'secret-pass');

        expect(User::withTrashed()->find($this->user->id))->toBeNull()
            ->and(Hiker::find($this->hiker->id))->toBeNull()
            ->and(PersonalAccessToken::count())->toBe(0)
            ->and(DB::table('sessions')->where('user_id', $this->user->id)->exists())->toBeFalse()
            ->and(DB::table('point_transactions')->count())->toBe(0)
            ->and(DB::table('reward_redemptions')->count())->toBe(0)
            ->and(XApplication::count())->toBe(0)
            ->and(Subscriber::count())->toBe(0);

        // Kept for the club's records, without any personal detail.
        expect($booking->fresh())
            ->hiker_id->toBeNull()
            ->name->toBe('Deleted member')
            ->phone->toBe('deleted')
            ->email->toBeNull()
            ->emergency_contact_name->toBe('Deleted member')
            ->emergency_contact_phone->toBe('deleted')
            ->notes->toBeNull()
            ->attendance->toBe(Attendance::Attended)
            ->paid_at->not->toBeNull()
            ->payment_reference->toBe('TX-1');
        expect($order->fresh())
            ->hiker_id->toBeNull()
            ->name->toBe('Deleted member')
            ->phone->toBe('deleted')
            ->email->toBeNull()
            ->delivery_city->toBeNull()
            ->delivery_address->toBeNull()
            ->notes->toBeNull()
            ->total_cents->toBe(2400);
    });

    it('leaves other members and subscribers alone', function () {
        $other = Hiker::factory()->create(['phone' => '+96171999888', 'email' => 'other@example.com']);
        $otherBooking = pastBooking($other);
        Subscriber::factory()->create(['email' => 'other@example.com']);

        app(DeleteAccount::class)->handle($this->user, 'secret-pass');

        expect($otherBooking->fresh()->name)->toBe($other->name)
            ->and(Subscriber::count())->toBe(1)
            ->and(Hiker::find($other->id))->not->toBeNull();
    });

    it('never deletes other subscribers when the member has no email or phone', function () {
        $user = User::factory()->create(['email' => null, 'phone' => null, 'password' => 'secret-pass']);
        $user->assignRole(Role::Member->value);
        Subscriber::factory()->count(3)->create();

        app(DeleteAccount::class)->handle($user, 'secret-pass');

        expect(Subscriber::count())->toBe(3);
    });

    it('lets a member book again later as a new hiker', function () {
        app(DeleteAccount::class)->handle($this->user, 'secret-pass');

        $event = Event::factory()->published()->withMeetingPoints(1)->create(['starts_on' => now()->addDays(10)->toDateString(), 'fee_cents' => 0, 'difficulty_level' => DifficultyLevel::Foundation]);
        $this->post(route('events.register.store', $event), [
            'name' => 'Rima Haddad', 'phone' => '+96170123456', 'meeting_point_id' => $event->meetingPoints()->value('id'),
            'emergency_contact_name' => 'Karim', 'emergency_contact_phone' => '+9613000000', 'terms' => '1',
        ])->assertSessionHasNoErrors()->assertSessionMissing('error')->assertRedirect();

        expect(Hiker::where('phone', '+96170123456')->sole()->id)->not->toBe($this->hiker->id);
    });
});

describe('refusing', function () {
    it('refuses a wrong password, staff, and members with upcoming bookings or open orders', function (Closure $setup, string $message) {
        $setup->call($this);

        expect(fn () => app(DeleteAccount::class)->handle($this->user->fresh(), $this->password ?? 'secret-pass'))
            ->toThrow(MemberException::class, $message);
        expect(User::find($this->user->id))->not->toBeNull();
    })->with([
        'wrong password' => [function () {
            $this->password = 'wrong';
        }, 'This password is not right.'],
        'staff' => [function () {
            $this->user->syncRoles([Role::Guide->value]);
        }, 'Staff accounts are deleted by a super admin'],
        'upcoming booking' => [function () {
            EventRegistration::factory()->for(Event::factory()->published()->create(['starts_on' => now()->addDays(5)->toDateString()]))->create(['hiker_id' => $this->hiker->id, 'phone' => $this->hiker->phone]);
        }, 'You still have upcoming bookings or open shop orders.'],
        'open order' => [function () {
            shopOrder($this->hiker, OrderStatus::Preparing);
        }, 'You still have upcoming bookings or open shop orders.'],
    ]);

    it('allows it once the upcoming booking is cancelled', function () {
        EventRegistration::factory()->cancelled()->for(Event::factory()->published()->create(['starts_on' => now()->addDays(5)->toDateString()]))->create(['hiker_id' => $this->hiker->id, 'phone' => $this->hiker->phone]);

        app(DeleteAccount::class)->handle($this->user, 'secret-pass');

        expect(User::find($this->user->id))->toBeNull();
    });
});

describe('in the app (API)', function () {
    it('deletes the account with the password and logs out every device', function () {
        $token = $this->user->createToken('Phone')->plainTextToken;
        $this->user->createToken('Tablet');

        $this->withToken($token)->deleteJson(route('api.v1.me.destroy'), ['password' => 'secret-pass'])->assertNoContent();

        expect(User::find($this->user->id))->toBeNull()->and(PersonalAccessToken::count())->toBe(0);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson(route('api.v1.me'))->assertUnauthorized();
    });

    it('answers the rules with a message', function () {
        $token = $this->user->createToken('Phone')->plainTextToken;

        $this->withToken($token)->deleteJson(route('api.v1.me.destroy'), [])->assertJsonValidationErrors('password');
        $this->withToken($token)->deleteJson(route('api.v1.me.destroy'), ['password' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This password is not right.');
        $this->app['auth']->forgetGuards();
        $this->withoutToken()->deleteJson(route('api.v1.me.destroy'), ['password' => 'secret-pass'])->assertUnauthorized();
    });
});

describe('on the website', function () {
    it('confirms with the password, logs out and explains what happened', function () {
        $this->actingAs($this->user)->get(route('account'))->assertSee('Delete my account')->assertSee(route('account.delete'));
        $this->get(route('account.delete'))->assertOk()->assertSee("This can't be undone.");

        $this->post(route('account.delete.destroy'), ['password' => 'secret-pass'])
            ->assertRedirect(route('delete-account'))
            ->assertSessionHas('status', 'Your account is deleted. Thank you for hiking with us!');

        $this->assertGuest();
        expect(User::find($this->user->id))->toBeNull();
    });

    it('shows the rule message and keeps the account', function () {
        $this->actingAs($this->user)->from(route('account.delete'))->post(route('account.delete.destroy'), ['password' => 'wrong'])
            ->assertRedirect(route('account.delete'))
            ->assertSessionHas('error', 'This password is not right.');

        $this->assertAuthenticatedAs($this->user);
    });

    it('has a public page explaining how to delete an account', function () {
        $this->get(route('delete-account'))->assertOk()->assertSee('What we keep')->assertSee('Deleted member');
        $this->get(route('account.delete'))->assertRedirect(route('login'));
    });
});
