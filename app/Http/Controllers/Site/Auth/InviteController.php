<?php

namespace App\Http\Controllers\Site\Auth;

use App\Domain\Members\Actions\ActivateAccount;
use App\Domain\Members\Exceptions\MemberException;
use App\Domain\Members\MemberLinks;
use App\Http\Controllers\Controller;
use App\Models\Hiker;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Pages behind the links organizers send (signed, expiring; see MemberLinks):
 * activate an account from an invite, or choose a new password.
 */
class InviteController extends Controller
{
    public function showActivation(Request $request, Hiker $hiker): View|RedirectResponse
    {
        if ($hiker->user_id !== null) {
            return redirect()->route('login')->with('error', 'This account is already active. Log in, or ask us for a new password link.');
        }

        return view('site.auth.activate-password', ['hiker' => $hiker, 'action' => $request->fullUrl()]);
    }

    public function activate(Request $request, Hiker $hiker, ActivateAccount $action): RedirectResponse
    {
        $request->validate(['password' => ['required', 'confirmed', Password::min((int) config('members.password_min_length'))]]);

        try {
            $user = $action->handle($hiker, $request->string('password')->toString());
        } catch (MemberException $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'Your account is ready. Welcome!');
    }

    public function showPassword(Request $request, User $user, MemberLinks $links): View|RedirectResponse
    {
        if (! $this->passwordLinkWorks($request, $user, $links)) {
            return redirect()->route('login')->with('error', 'This link was already used or is no longer valid. Ask us for a new one.');
        }

        return view('site.auth.set-password', ['user' => $user, 'action' => $request->fullUrl()]);
    }

    public function setPassword(Request $request, User $user, MemberLinks $links): RedirectResponse
    {
        if (! $this->passwordLinkWorks($request, $user, $links)) {
            return redirect()->route('login')->with('error', 'This link was already used or is no longer valid. Ask us for a new one.');
        }

        $request->validate(['password' => ['required', 'confirmed', Password::min((int) config('members.password_min_length'))]]);

        // A new password also signs out "remember me" sessions and app logins elsewhere, and invalidates this link.
        $user->forceFill(['password' => $request->string('password')->toString(), 'remember_token' => null])->save();
        $user->tokens()->delete();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'Your password has been changed.');
    }

    private function passwordLinkWorks(Request $request, User $user, MemberLinks $links): bool
    {
        return $user->is_active && $links->passwordLinkIsCurrent($user, (string) $request->query('v'));
    }
}
