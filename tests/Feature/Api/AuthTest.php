<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Members\Actions\SetAccountActive;
use App\Domain\Members\MemberLinks;
use App\Mail\PasswordResetCodeMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    $event = Event::factory()->published()->create(['starts_on' => now()->addDays(10)->toDateString()]);
    $this->booking = EventRegistration::factory()->for($event)->create([
        'name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'reference' => 'K7F3Q2MZ',
    ]);
    $this->hiker = $this->booking->hiker;
});

function apiMember(array $attributes = []): User
{
    $user = User::factory()->create($attributes + ['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'password' => 'secret-pass']);
    $user->assignRole(Role::Member->value);

    return $user;
}

/**
 * The 6-digit code from the last password-reset email.
 */
function apiResetCode(): string
{
    $code = '';
    Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    return $code;
}

describe('login', function () {
    it('returns a token and the member with points and rank', function () {
        $user = apiMember();
        $this->hiker->update(['user_id' => $user->id]);
        $this->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 140, 'description' => 'Welcome back']);

        $response = $this->postJson(route('api.v1.auth.login'), ['phone' => '+961 70-123 456', 'password' => 'secret-pass', 'device_name' => "Rima's Pixel"])
            ->assertCreated()
            ->assertJsonPath('member.name', 'Rima Haddad')
            ->assertJsonPath('member.phone', '+96170123456')
            ->assertJsonPath('member.hiker.points', 140)
            ->assertJsonPath('member.hiker.rank', ['key' => 'trekker', 'label' => 'Trekker'])
            ->assertJsonPath('member.hiker.next_rank', ['key' => 'mountaineer', 'label' => 'Mountaineer', 'points_needed' => 60])
            ->assertJsonPath('member.hiker.rank_progress_percent', 50);

        expect(PersonalAccessToken::sole()->name)->toBe("Rima's Pixel")
            ->and($user->fresh()->last_login_at)->not->toBeNull();

        $this->withToken($response->json('token'))->getJson(route('api.v1.me'))
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.hiker.points', 140);
    });

    it('gives the same error for a wrong password, an unknown number and a deactivated account', function (array $input, array $member) {
        apiMember($member);

        $this->postJson(route('api.v1.auth.login'), $input + ['device_name' => 'Phone'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', "This phone number and password don't match an account.");

        expect(PersonalAccessToken::count())->toBe(0);
    })->with([
        'wrong password' => [['phone' => '+96170123456', 'password' => 'wrong-pass'], []],
        'unknown number' => [['phone' => '+96171999999', 'password' => 'secret-pass'], []],
        'deactivated' => [['phone' => '+96170123456', 'password' => 'secret-pass'], ['is_active' => false]],
    ]);

    it('locks the number after 5 wrong tries, like the website', function () {
        apiMember();

        foreach (range(1, 5) as $try) {
            $this->postJson(route('api.v1.auth.login'), ['phone' => '+96170123456', 'password' => 'wrong', 'device_name' => 'Phone'])->assertUnprocessable();
        }

        $this->postJson(route('api.v1.auth.login'), ['phone' => '+96170123456', 'password' => 'secret-pass', 'device_name' => 'Phone'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', fn (string $message) => str_contains($message, 'Too many login attempts'));
    });

    it('needs a device name', function () {
        apiMember();

        $this->postJson(route('api.v1.auth.login'), ['phone' => '+96170123456', 'password' => 'secret-pass'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('device_name');
    });

    it('does not start a website session', function () {
        apiMember();

        $this->postJson(route('api.v1.auth.login'), ['phone' => '+96170123456', 'password' => 'secret-pass', 'device_name' => 'Phone'])
            ->assertCreated()
            ->assertCookieMissing(config('session.cookie'));
    });

    it('works for an account without a hiker profile', function () {
        $user = apiMember(['phone' => '+96171555555']);

        $token = $user->createToken('Phone')->plainTextToken;

        $this->withToken($token)->getJson(route('api.v1.me'))->assertOk()->assertJsonPath('data.hiker', null);
    });
});

describe('logout', function () {
    it('revokes only this device, or every device', function () {
        $user = apiMember();
        $phone = $user->createToken('Phone')->plainTextToken;
        $tablet = $user->createToken('Tablet')->plainTextToken;
        $laptop = $user->createToken('Laptop')->plainTextToken;

        $this->withToken($phone)->postJson(route('api.v1.auth.logout'))->assertNoContent();
        expect($user->tokens()->pluck('name')->all())->toEqualCanonicalizing(['Tablet', 'Laptop']);

        $this->app['auth']->forgetGuards();
        $this->withToken($tablet)->postJson(route('api.v1.auth.logout-all'))->assertNoContent();
        expect($user->tokens()->count())->toBe(0);

        $this->app['auth']->forgetGuards();
        $this->withToken($laptop)->getJson(route('api.v1.me'))->assertUnauthorized();
    });

    it('needs a token', function () {
        $this->getJson(route('api.v1.me'))->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->postJson(route('api.v1.auth.logout'))->assertUnauthorized();
    });

    it('ignores a website session', function () {
        $this->actingAs(apiMember(), 'web')->getJson(route('api.v1.me'))->assertUnauthorized();
    });
});

describe('deactivation', function () {
    it('revokes the app logins when an organizer deactivates the account', function () {
        $user = apiMember();
        $this->hiker->update(['user_id' => $user->id]);
        $token = $user->createToken('Phone')->plainTextToken;

        app(SetAccountActive::class)->handle($this->hiker->fresh(), false);

        expect($user->tokens()->count())->toBe(0);
        $this->withToken($token)->getJson(route('api.v1.me'))->assertUnauthorized();
    });

    it('rejects a deactivated account that still has a token', function () {
        $user = apiMember();
        $token = $user->createToken('Phone')->plainTextToken;
        $user->forceFill(['is_active' => false])->save();

        $this->withToken($token)->getJson(route('api.v1.me'))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'This account has been deactivated. Message us on WhatsApp if you think this is a mistake.');

        expect($user->tokens()->count())->toBe(0);
    });
});

describe('activation', function () {
    it('activates an account and logs the app in, in one step', function () {
        $this->postJson(route('api.v1.auth.activate'), [
            'phone' => '+961 70 123 456',
            'reference' => ' k7f3q2mz ',
            'password' => 'trail-mix-42',
            'password_confirmation' => 'trail-mix-42',
            'device_name' => 'Phone',
        ])
            ->assertCreated()
            ->assertJsonPath('member.name', 'Rima Haddad')
            ->assertJsonPath('member.hiker.points', 0)
            ->assertJsonStructure(['token']);

        $user = User::where('phone', '+96170123456')->sole();
        expect($user->hasRole(Role::Member->value))->toBeTrue()
            ->and(Hash::check('trail-mix-42', $user->password))->toBeTrue()
            ->and($this->hiker->fresh()->user_id)->toBe($user->id)
            ->and($user->tokens()->count())->toBe(1);
    });

    it('refuses a wrong reference with the website message', function () {
        $this->postJson(route('api.v1.auth.activate'), [
            'phone' => '+96170123456', 'reference' => 'AAAAAAAA', 'password' => 'trail-mix-42', 'password_confirmation' => 'trail-mix-42', 'device_name' => 'Phone',
        ])
            ->assertUnprocessable()
            ->assertExactJson(['message' => "We couldn't match this phone number and booking reference. Check both on your booking confirmation."]);

        expect(User::count())->toBe(0);
    });

    it('tells hikers who already have an account to log in', function () {
        apiMember();

        $this->postJson(route('api.v1.auth.activate'), [
            'phone' => '+96170123456', 'reference' => 'K7F3Q2MZ', 'password' => 'trail-mix-42', 'password_confirmation' => 'trail-mix-42', 'device_name' => 'Phone',
        ])->assertUnprocessable()->assertJsonPath('message', 'This phone number already has an account. Log in, or reset your password if you forgot it.');
    });

    it('validates the password', function () {
        $this->postJson(route('api.v1.auth.activate'), [
            'phone' => '+96170123456', 'reference' => 'K7F3Q2MZ', 'password' => 'short', 'password_confirmation' => 'short', 'device_name' => 'Phone',
        ])->assertJsonValidationErrors('password');
    });
});

describe('password reset', function () {
    beforeEach(fn () => Mail::fake());

    it('emails a code, sets the new password, logs out other devices and logs this one in', function () {
        $user = apiMember();
        $user->createToken('Old phone');

        $this->postJson(route('api.v1.auth.password.forgot'), ['phone' => '+961 70 123 456'])
            ->assertAccepted()
            ->assertJsonPath('message', 'If this number has an account, we sent it a code. It is valid for 10 minutes.');

        $this->postJson(route('api.v1.auth.password.reset'), [
            'phone' => '+96170123456', 'code' => apiResetCode(), 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123', 'device_name' => 'New phone',
        ])->assertCreated()->assertJsonPath('member.id', $user->id);

        expect(Hash::check('new-pass-123', $user->fresh()->password))->toBeTrue()
            ->and($user->tokens()->pluck('name')->all())->toBe(['New phone']);
    });

    it('answers the same for a number without an account', function () {
        $this->postJson(route('api.v1.auth.password.forgot'), ['phone' => '+96179999999'])->assertAccepted();

        Mail::assertNothingSent();
    });

    it('rejects a wrong code', function () {
        apiMember();
        $this->postJson(route('api.v1.auth.password.forgot'), ['phone' => '+96170123456']);
        $wrong = apiResetCode() === '000000' ? '111111' : '000000';

        $this->postJson(route('api.v1.auth.password.reset'), [
            'phone' => '+96170123456', 'code' => $wrong, 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123', 'device_name' => 'Phone',
        ])->assertUnprocessable()->assertJsonPath('message', 'This code is wrong or has expired. Ask for a new one.');

        $this->postJson(route('api.v1.auth.password.reset'), [
            'phone' => '+96179999999', 'code' => $wrong, 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123', 'device_name' => 'Phone',
        ])->assertUnprocessable()->assertJsonPath('message', 'This code is wrong or has expired. Ask for a new one.');
    });

    it('also logs out the app when a new password is set from an organizer link', function () {
        $user = apiMember();
        $user->createToken('Phone');

        // The signed link is the same URL for the form (GET) and its submission (POST).
        $this->post(app(MemberLinks::class)->password($user), ['password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
            ->assertRedirect(route('account'));

        expect($user->tokens()->count())->toBe(0);
    });
});
