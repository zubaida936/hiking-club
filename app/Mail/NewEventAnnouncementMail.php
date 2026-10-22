<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;

class NewEventAnnouncementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Event $event,
        public readonly Subscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New hike: {$this->event->title} · {$this->event->starts_on->format('D j M')}");
    }

    public function headers(): Headers
    {
        // Lets mail apps show their own "Unsubscribe" button.
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.newsletter.new-event', with: [
            // The event's own page once it exists; the events list until then.
            'eventUrl' => Route::has('events.show') ? route('events.show', $this->event) : route('events.index'),
            'unsubscribeUrl' => $this->unsubscribeUrl(),
        ]);
    }

    private function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', $this->subscriber->token);
    }
}
