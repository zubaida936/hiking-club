<?php

namespace App\Http\Controllers\Site\Auth;

use App\Domain\Members\Actions\ActivateAccount;
use App\Domain\Members\Exceptions\MemberException;
use App\Http\Controllers\Controller;
use App\Models\Hiker;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Two steps: prove the phone number with a booking reference, then choose a password.
 * The verified hiker is kept in the session between the steps.
 */
class ActivationController extends Controller
{
    private const SESSION_KEY = 'activation.hiker_id';

    public function create(): View
    {
        return view('site.auth.activate');
    }

    public function verify(Request $request, ActivateAccount $action): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'reference' => ['required', 'string', 'max:20'],
        ], ['phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.']);

        try {
            $hiker = $action->verify((string) Phone::normalize($request->input('phone')), $request->string('reference')->toString());
        } catch (MemberException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, $hiker->id);

        return redirect()->route('activate.password');
    }

    public function password(Request $request): View|RedirectResponse
    {
        $hiker = $this->verifiedHiker($request);

        return $hiker ? view('site.auth.activate-password', ['hiker' => $hiker]) : redirect()->route('activate');
    }

    public function store(Request $request, ActivateAccount $action): RedirectResponse
    {
        $hiker = $this->verifiedHiker($request);
        if ($hiker === null) {
            return redirect()->route('activate');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::min((int) config('members.password_min_length'))],
        ]);

        try {
            $user = $action->handle($hiker, $request->string('password')->toString());
        } catch (MemberException $e) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('login')->with('error', $e->getMessage());
        }

        $request->session()->forget(self::SESSION_KEY);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'Your account is ready. Welcome!');
    }

    private function verifiedHiker(Request $request): ?Hiker
    {
        $id = $request->session()->get(self::SESSION_KEY);

        return $id ? Hiker::find($id) : null;
    }
}
