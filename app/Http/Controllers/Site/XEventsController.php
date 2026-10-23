<?php

namespace App\Http\Controllers\Site;

use App\Domain\XEvents\Actions\SubmitApplication;
use App\Domain\XEvents\Exceptions\XApplicationException;
use App\Domain\XEvents\XAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\XApplicationRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * X Events: the landing page and the "apply once" form.
 */
class XEventsController extends Controller
{
    public function index(Request $request, XAccess $access): View
    {
        $hiker = $request->user()?->hiker;

        return view('site.x-events.index', [
            'events' => Event::published()->upcoming()->where('is_xtreme', true)->withSeatCounts()->orderBy('starts_on')->get(),
            'hasAccess' => $access->allows($hiker),
            'application' => $hiker?->latestXApplication(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('site.x-events.apply', ['member' => $request->user()?->hiker]);
    }

    public function store(XApplicationRequest $request, SubmitApplication $action): RedirectResponse
    {
        try {
            $action->handle($request->toData());
        } catch (XApplicationException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('x-events.applied');
    }

    public function applied(): View
    {
        return view('site.x-events.applied');
    }
}
