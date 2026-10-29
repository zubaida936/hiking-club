<?php

namespace App\Http\Controllers\Site;

use App\Domain\Loyalty\Leaderboard;
use App\Domain\Members\Actions\DeleteAccount;
use App\Domain\Members\Actions\SetLeaderboardVisibility;
use App\Domain\Members\Exceptions\MemberException;
use App\Domain\Registrations\Actions\CancelOwnBooking;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Domain\Rewards\HikerRewards;
use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The member dashboard: points and rank, upcoming bookings, hike history and rewards.
 */
class AccountController extends Controller
{
    public function show(Request $request, Leaderboard $leaderboard, HikerRewards $rewards): View
    {
        $hiker = $request->user()?->hiker;

        if ($hiker === null) {
            return view('site.account.show', ['hiker' => null]);
        }

        $hiker->loadSum('pointTransactions as total_points', 'points');

        $upcoming = $hiker->registrations()
            ->where('status', '!=', RegistrationStatus::Cancelled)
            ->whereHas('event', fn ($q) => $q->upcoming())
            ->with('event')
            ->get()
            ->sortBy(fn (EventRegistration $r) => $r->event->starts_on)
            ->values();

        // Completed hikes, newest first, with the points this hiker earned from each.
        $history = $hiker->registrations()
            ->where('status', RegistrationStatus::Confirmed)
            ->where('attendance', Attendance::Attended)
            ->with('event')
            ->withSum(['pointTransactions as earned_points' => fn ($q) => $q->where('hiker_id', $hiker->id)], 'points')
            ->get()
            ->sortByDesc(fn (EventRegistration $r) => $r->event->starts_on)
            ->values();

        return view('site.account.show', [
            'hiker' => $hiker,
            'position' => $leaderboard->publicPositionOf($hiker),
            'upcoming' => $upcoming,
            'history' => $history,
            'rewards' => $rewards->statesFor($hiker),
            'recentPoints' => $hiker->pointTransactions()->latest('id')->limit(5)->get(),
        ]);
    }

    public function leaderboardVisibility(Request $request, SetLeaderboardVisibility $action): RedirectResponse
    {
        $hiker = $request->user()?->hiker;
        abort_if($hiker === null, 404);

        $visible = $request->boolean('show_on_leaderboard');
        $action->handle($hiker, $visible);

        return back()->with('success', $visible ? 'You are shown on the leaderboard.' : 'You are hidden from the public leaderboard.');
    }

    public function confirmDelete(): View
    {
        return view('site.account.delete');
    }

    /**
     * Delete the account (DeleteAccount: anonymises bookings and orders, deletes the login), then log out.
     */
    public function destroy(Request $request, DeleteAccount $action): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        /** @var User $user */
        $user = $request->user();

        try {
            $action->handle($user, $request->string('password')->toString());
        } catch (MemberException $e) {
            return back()->with('error', $e->getMessage());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('delete-account')->with('status', 'Your account is deleted. Thank you for hiking with us!');
    }

    public function cancelBooking(Request $request, EventRegistration $registration, CancelOwnBooking $action): RedirectResponse
    {
        Gate::authorize('cancelOwn', $registration);

        try {
            $action->handle($registration, $request->user());
        } catch (RegistrationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Your booking for {$registration->event->title} is cancelled.");
    }
}
