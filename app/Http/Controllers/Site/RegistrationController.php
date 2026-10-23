<?php

namespace App\Http\Controllers\Site;

use App\Domain\Registrations\Actions\RegisterForEvent;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Domain\Rewards\Exceptions\RewardException;
use App\Domain\Rewards\HikerRewards;
use App\Domain\XEvents\XAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\RegistrationRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(Request $request, Event $event, HikerRewards $rewards, XAccess $xAccess): View
    {
        abort_unless($event->isVisibleOnSite(), 404);

        $event->load('meetingPoints')->loadSeatCount();
        $member = $request->user()?->hiker;

        return view('site.registrations.create', [
            'event' => $event,
            // Logged-in members get their details filled in, and can use a reward.
            'member' => $member,
            'rewards' => $member ? $rewards->discountsFor($member, $event) : [],
            // X events: only approved hikers (or Elite) see the form.
            'xLocked' => $event->is_xtreme && ! $xAccess->allows($member),
        ]);
    }

    public function store(RegistrationRequest $request, Event $event, RegisterForEvent $action): RedirectResponse
    {
        try {
            $registration = $action->handle($event, $request->toData());
        } catch (RegistrationException|RewardException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('registrations.show', $registration);
    }

    /**
     * The hiker's own booking, reached through the secret link (redirect after booking, email).
     */
    public function show(EventRegistration $registration): View
    {
        $event = $registration->event;

        return view('site.registrations.show', [
            'registration' => $registration,
            'event' => $event,
            'whatsappUrl' => WhatsApp::link("Hi! I booked \"{$event->title}\" on {$event->starts_on->format('D, j M Y')}. My booking reference is {$registration->reference}."),
        ]);
    }
}
