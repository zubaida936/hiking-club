<?php

namespace App\Http\Controllers\Site;

use App\Domain\Loyalty\Leaderboard;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The member's own page: points, tier and upcoming bookings. The full member
 * dashboard (ClickUp "Member dashboard") builds on this.
 */
class AccountController extends Controller
{
    public function __invoke(Request $request, Leaderboard $leaderboard): View
    {
        $hiker = $request->user()?->hiker;

        if ($hiker === null) {
            return view('site.account.show', ['hiker' => null]);
        }

        $hiker->loadSum('pointTransactions as total_points', 'points');

        return view('site.account.show', [
            'hiker' => $hiker,
            'position' => $leaderboard->positionOf($hiker),
            'upcoming' => $hiker->registrations()
                ->where('status', '!=', RegistrationStatus::Cancelled)
                ->whereHas('event', fn ($q) => $q->upcoming())
                ->with('event')
                ->get()
                ->sortBy(fn ($r) => $r->event->starts_on)
                ->values(),
            'recentPoints' => $hiker->pointTransactions()->latest('id')->limit(5)->get(),
        ]);
    }
}
