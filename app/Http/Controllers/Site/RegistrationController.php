<?php

namespace App\Http\Controllers\Site;

use App\Domain\Registrations\Actions\RegisterForEvent;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\RegistrationRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(Event $event): View
    {
        abort_unless($event->isVisibleOnSite(), 404);

        $event->load('meetingPoints')->loadSeatCount();

        return view('site.registrations.create', ['event' => $event]);
    }

    public function store(RegistrationRequest $request, Event $event, RegisterForEvent $action): RedirectResponse
    {
        try {
            $registration = $action->handle($event, $request->toData());
        } catch (RegistrationException $e) {
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
