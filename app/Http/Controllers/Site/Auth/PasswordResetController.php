<?php

namespace App\Http\Controllers\Site\Auth;

use App\Domain\Members\Actions\ResetPassword;
use App\Domain\Members\Codes\OneTimeCodes;
use App\Domain\Members\Exceptions\MemberException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Forgot password: send a code to the member, then set a new password with it.
 * The answer is the same whether or not the number has an account.
 */
class PasswordResetController extends Controller
{
    private const SESSION_KEY = 'password_reset.phone';

    public function create(): View
    {
        return view('site.auth.forgot-password');
    }

    public function send(Request $request, OneTimeCodes $codes): RedirectResponse
    {
        $request->validate(['phone' => ['required', 'string', 'regex:'.Phone::PATTERN]], [
            'phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.',
        ]);

        $phone = (string) Phone::normalize($request->input('phone'));
        $user = User::where('phone', $phone)->where('is_active', true)->first();

        if ($user !== null && $codes->canReach($user)) {
            $codes->issue($user, OneTimeCodes::PASSWORD_RESET);
        }

        $request->session()->put(self::SESSION_KEY, $phone);

        return redirect()->route('password.reset');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_KEY);

        return $phone ? view('site.auth.reset-password', ['phone' => $phone]) : redirect()->route('password.request');
    }

    public function update(Request $request, ResetPassword $action): RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_KEY);
        if (! $phone) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min((int) config('members.password_min_length'))],
        ]);

        $user = User::where('phone', $phone)->where('is_active', true)->first();

        try {
            if ($user === null) {
                throw MemberException::invalidCode();
            }
            $action->handle($user, $request->string('code')->toString(), $request->string('password')->toString());
        } catch (MemberException $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->session()->forget(self::SESSION_KEY);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'Your password has been changed.');
    }
}
