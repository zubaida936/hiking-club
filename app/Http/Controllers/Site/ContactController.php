<?php

namespace App\Http\Controllers\Site;

use App\Domain\Contact\Actions\SendContactMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        $contact = config('site.contact');

        return view('site.contact', [
            'contact' => $contact,
            // A Google Maps search for the address unless the club set its own map link.
            'mapsUrl' => $contact['maps_url'] ?: 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($contact['address']),
        ]);
    }

    public function store(ContactRequest $request, SendContactMessage $action): RedirectResponse
    {
        $action->handle($request->toData());

        return redirect()->route('contact')->with('status', 'Thank you! Your message is on its way. We usually answer within a day.');
    }
}
