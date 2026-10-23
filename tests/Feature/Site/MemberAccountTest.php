<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Mail\PasswordResetCodeMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\OneTimeCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->event = Event::factory()->published()->create(['title' => 'Horch Ehden Nature Reserve', 'starts_on' => now()->addDays(10)->toDateString()]);
    $this->booking = EventRegistration::factory()->for($this->event)->create([
        'name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'reference' => 'K7F3Q2MZ',
    ]);
    $this->hiker = $this->booking->hiker;
});

function member(array $attributes = []): User
{
    $user = User::factory()->create($attributes + ['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => null, 'password' => 'secret-pass']);
    $user->assignRole(Role::Member->value);

    return $user;
}

describe('activation', function () {
    it('activates an account with a booking reference and a new password', function () {
        $this->get(route('activate'))->assertOk()->assertSee('Booking reference');

        $this->post(route('activate.verify'), ['phone' => '+961 70-123 456', 'reference' => ' k7f3q2mz '])
            ->assertRedirect(route('activate.password'));
        $this->get(route('activate.password'))->assertSee('Hi Rima Haddad!')->assertSee('+96170123456');

        $this->post(route('activate.store'), ['password' => 'trail-mix-42', 'password_confirmation' => 'trail-mix-42'])
            ->assertRedirect(route('account'));

        $user = User::where('phone', '+96170123456')->sole();
        expect($user)->name->toBe('Rima Haddad')->email->toBe('rima@example.com')
            ->and($user->hasRole(Role::Member->value))->toBeTrue()
            ->and($user->canAccessAdmin())->toBeFalse()
            ->and(Hash::check('trail-mix-42', $user->password))->toBeTrue()
            ->and($this->hiker->fresh()->user_id)->toBe($user->id);
        $this->assertAuthenticatedAs($user);
    });

    it('refuses a wrong reference or a number without bookings', function (array $input) {
        $this->from(route('activate'))->post(route('activate.verify'), $input)
            ->assertRedirect(route('activate'))
            ->assertSessionHas('error', "We couldn't match this phone number and booking reference. Check both on your booking confirmation.");

        $this->get(route('activate.password'))->assertRedirect(route('activate'));
    })->with([
        'wrong reference' => [['phone' => '+96170123456', 'reference' => 'AAAAAAAA']],
        'reference of someone else' => [['phone' => '+96171000000', 'reference' => 'K7F3Q2MZ']],
    ]);

    it('tells hikers who already have an account to log in', function () {
        member();

        $this->post(route('activate.verify'), ['phone' => '+96170123456', 'reference' => 'K7F3Q2MZ'])
            ->assertSessionHas('error', 'This phone number already has an account. Log in, or reset your password if you forgot it.');
    });

    it('needs a confirmed password of at least 8 characters', function () {
        $this->post(route('activate.verify'), ['phone' => '+96170123456', 'reference' => 'K7F3Q2MZ']);

        $this->post(route('activate.store'), ['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->post(route('activate.store'), ['password' => 'long-enough-1', 'password_confirmation' => 'different-1'])->assertSessionHasErrors('password');
        expect(User::count())->toBe(0);
    });

    it('leaves the email empty when another account already uses it', function () {
        User::factory()->create(['email' => 'rima@example.com']);

        $this->post(route('activate.verify'), ['phone' => '+96170123456', 'reference' => 'K7F3Q2MZ']);
        $this->post(route('activate.store'), ['password' => 'trail-mix-42', 'password_confirmation' => 'trail-mix-42']);

        expect(User::where('phone', '+96170123456')->sole()->email)->toBeNull();
    });
});

describe('login', function () {
    it('logs in with phone and password', function () {
        $user = member();

        $this->get(route('login'))->assertOk()->assertSee('Forgot password?')->assertSee(route('activate'));
        $this->post(route('login.store'), ['phone' => '+961 70 123 456', 'password' => 'secret-pass'])
            ->assertRedirect(route('account'));

        $this->assertAuthenticatedAs($user);
        expect($user->fresh()->last_login_at)->not->toBeNull();
    });

    it('gives the same error for a wrong password, an unknown number or a deactivated account', function (Closure $setup, array $input) {
        $setup();

        $this->post(route('login.store'), $input)->assertSessionHasErrors(['phone' => "This phone number and password don't match an account."]);
        $this->assertGuest();
    })->with([
        'wrong password' => [fn () => member(), ['phone' => '+96170123456', 'password' => 'nope']],
        'unknown number' => [fn () => member(), ['phone' => '+96171111111', 'password' => 'secret-pass']],
        'deactivated' => [fn () => member(['is_active' => false]), ['phone' => '+96170123456', 'password' => 'secret-pass']],
    ]);

    it('locks out after 5 wrong attempts', function () {
        member();

        foreach (range(1, 5) as $i) {
            $this->post(route('login.store'), ['phone' => '+96170123456', 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['phone' => '+96170123456', 'password' => 'secret-pass'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest();
    });

    it('sends guests to the member login and members away from the login page', function () {
        $this->get(route('account'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        $this->actingAs(member())->get(route('login'))->assertRedirect(route('account'));
        $this->get(route('admin.login'))->assertRedirect(route('account'));
    });

    it('logs out', function () {
        $this->actingAs(member())->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    });
});

describe('password reset', function () {
    beforeEach(fn () => Mail::fake());

    function sentCode(): string
    {
        $code = '';
        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    it('emails a code and sets a new password with it', function () {
        $user = member(['email' => 'rima@example.com']);

        $this->get(route('password.request'))->assertOk();
        $this->post(route('password.email'), ['phone' => '+961 70 123 456'])->assertRedirect(route('password.reset'));
        $this->get(route('password.reset'))->assertSee("we've sent a 6-digit code", false);

        Mail::assertSent(PasswordResetCodeMail::class, fn ($mail) => $mail->hasTo('rima@example.com'));
        $code = sentCode();
        expect($code)->toMatch('/^\d{6}$/')
            ->and(OneTimeCode::sole()->code_hash)->not->toBe($code);

        $this->post(route('password.update'), ['code' => $code, 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
            ->assertRedirect(route('account'));

        expect(Hash::check('new-pass-123', $user->fresh()->password))->toBeTrue();
        $this->assertAuthenticatedAs($user);

        // A code works once.
        $this->post(route('logout'));
        $this->post(route('password.email'), ['phone' => '+96170123456']);
        Mail::assertSentCount(2);
    });

    it('rejects wrong and expired codes, and locks after 5 wrong tries', function () {
        member(['email' => 'rima@example.com']);
        $this->post(route('password.email'), ['phone' => '+96170123456']);
        $code = sentCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach (range(1, 5) as $i) {
            $this->post(route('password.update'), ['code' => $wrong, 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
                ->assertSessionHas('error', 'This code is wrong or has expired. Ask for a new one.');
        }
        $this->post(route('password.update'), ['code' => $code, 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
            ->assertSessionHas('error');

        OneTimeCode::query()->delete();
        $this->post(route('password.email'), ['phone' => '+96170123456']);
        $fresh = '';
        Mail::assertSent(PasswordResetCodeMail::class, function ($mail) use (&$fresh) {
            $fresh = $mail->code;

            return true;
        });
        $this->travel(11)->minutes();
        $this->post(route('password.update'), ['code' => $fresh, 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
            ->assertSessionHas('error');
        $this->assertGuest();
    });

    it('answers the same for unknown numbers and accounts without email', function () {
        member(); // no email

        $this->post(route('password.email'), ['phone' => '+96170123456'])->assertRedirect(route('password.reset'));
        $this->post(route('password.email'), ['phone' => '+96179999999'])->assertRedirect(route('password.reset'));

        Mail::assertNothingSent();
        $this->get(route('password.reset'))->assertSee('No email on your account?');
    });

    it('can write codes to the log for local development', function () {
        config(['members.code_channel' => 'log']);
        member(); // no email needed
        Log::shouldReceive('info')->once()->withArgs(fn ($message) => str_starts_with($message, 'Password reset code for +96170123456: '));

        $this->post(route('password.email'), ['phone' => '+96170123456']);
    });
});

describe('account page', function () {
    it('shows points, tier, rank and upcoming hikes', function () {
        $user = member();
        $this->hiker->update(['user_id' => $user->id]);
        $this->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 85, 'description' => 'Welcome back']);

        $this->actingAs($user)->get(route('account'))
            ->assertOk()
            ->assertSee('Hi Rima!')
            ->assertSee('85')
            ->assertSee('Trekker')
            ->assertSee('115 points to Mountaineer')
            ->assertSee('#1')
            ->assertSee('Horch Ehden Nature Reserve')
            ->assertSee(route('registrations.show', $this->booking))
            ->assertSee('Welcome back');
    });

    it('works for accounts without hikes', function () {
        $staff = userWithRole(Role::Admin);

        $this->actingAs($staff)->get(route('account'))->assertOk()->assertSee('There are no hikes on this account yet.');
    });

    it('is linked from the header', function () {
        $this->get(route('home'))->assertSee(route('account'));
        expect(Hiker::count())->toBe(1);
    });
});
