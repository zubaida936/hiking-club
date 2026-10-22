<?php

namespace App\Http\Controllers\Site;

use App\Domain\Newsletter\Actions\ConfirmSubscription;
use App\Domain\Newsletter\Actions\Subscribe;
use App\Domain\Newsletter\Actions\Unsubscribe;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\SubscribeRequest;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function store(SubscribeRequest $request, Subscribe $action): RedirectResponse
    {
        $data = $request->toData();
        $action->handle($data);

        // Same answer whether or not the visitor was already on the list.
        $message = $data->email !== null
            ? 'Almost there! Check your inbox and click the link to confirm your email.'
            : "Thanks! You're on the list for Next Destination Alerts.";

        return redirect()->to(route('home').'#newsletter')->with('newsletter', $message);
    }

    public function confirm(Subscriber $subscriber, ConfirmSubscription $action): View
    {
        $action->handle($subscriber);

        return view('site.newsletter.confirmed');
    }

    public function showUnsubscribe(Subscriber $subscriber): View
    {
        return view('site.newsletter.unsubscribe', ['subscriber' => $subscriber]);
    }

    /**
     * A button on the page does this, so mail apps that open links in advance can't unsubscribe anyone.
     */
    public function unsubscribe(Subscriber $subscriber, Unsubscribe $action): View
    {
        $action->handle($subscriber);

        return view('site.newsletter.unsubscribed');
    }
}
