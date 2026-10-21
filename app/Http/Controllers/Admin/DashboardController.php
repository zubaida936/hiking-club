<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Events\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'Upcoming published events' => Event::published()->upcoming()->count(),
                'Draft events' => Event::where('status', EventStatus::Draft)->count(),
                'Staff users' => User::staff()->count(),
                'Active staff' => User::staff()->where('is_active', true)->count(),
            ],
            'nextEvent' => Event::published()->upcoming()->orderBy('starts_on')->first(),
        ]);
    }
}
