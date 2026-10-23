<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly EventRegistration $registration)
    {
        // Loaded relations are restored with the model when the queued mail runs.
        $registration->loadMissing('event');
    }

    public function envelope(): Envelope
    {
        $event = $this->registration->event;

        return new Envelope(subject: "Booking cancelled: {$event->title} · {$event->starts_on->format('D j M')} (ref {$this->registration->reference})");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.registrations.cancelled', with: [
            'event' => $this->registration->event,
        ]);
    }
}
