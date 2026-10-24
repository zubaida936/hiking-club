<?php

namespace App\Http\Controllers\Site;

use App\Domain\Content\HomepageHero;
use App\Domain\Loyalty\Leaderboard;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Leaderboard $leaderboard, HomepageHero $hero): View
    {
        return view('site.home', [
            'heroSlides' => $hero->slides(),
            'nextEvent' => Event::nextUpcoming()?->loadSeatCount(),
            'topHikers' => $leaderboard->top(10),
        ]);
    }
}
