<?php

namespace App\Mail;

use App\Models\EventRegistration;
use App\Models\EventUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A message from the organizers about a hike the hiker is booked on.
 */
class EventUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EventRegistration $registration,
        public readonly EventUpdate $eventUpdate,
    ) {
        // Loaded relations are restored with the model when the queued mail runs.
        $registration->loadMissing('event');
    }

    public function envelope(): Envelope
    {
        $event = $this->registration->event;

        return new Envelope(subject: "Update: {$event->title} · {$event->starts_on->format('D j M')}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.events.update', with: [
            'event' => $this->registration->event,
            'bookingUrl' => route('registrations.show', $this->registration),
        ]);
    }
}
