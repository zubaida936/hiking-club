<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home', [
            'nextEvent' => Event::published()->upcoming()
                ->orderBy('starts_on')->orderBy('id')
                ->with('meetingPoints')
                ->first(),
        ]);
    }
}
