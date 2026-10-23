<?php

namespace App\Http\Controllers\Site;

use App\Domain\Loyalty\Leaderboard;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Leaderboard $leaderboard): View
    {
        return view('site.home', [
            'nextEvent' => Event::nextUpcoming()?->loadSeatCount(),
            'topHikers' => $leaderboard->top(10),
        ]);
    }
}
