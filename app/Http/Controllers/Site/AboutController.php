<?php

namespace App\Http\Controllers\Site;

use App\Domain\Registrations\Enums\Attendance;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\TeamMember;
use Illuminate\View\View;

/**
 * The club's story, what it offers, the team (CMS → Team) and what hikers say.
 * Texts are placeholders in config/site.php → about_page.
 */
class AboutController extends Controller
{
    public function __invoke(): View
    {
        return view('site.about', [
            'about' => config('site.about_page'),
            'team' => TeamMember::shown()->get(),
            'stats' => [
                'hikes' => Event::published()->past()->count(),
                'hikers' => EventRegistration::where('attendance', Attendance::Attended)->distinct()->count('hiker_id'),
            ],
        ]);
    }
}
