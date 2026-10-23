<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a hiker books, joins the waitlist, or ($promoted) moves up from the waitlist.
 */
class RegistrationConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EventRegistration $registration,
        public readonly bool $promoted = false,
    ) {
        // Loaded relations are restored with the model when the queued mail runs.
        $registration->loadMissing('event');
    }

    public function envelope(): Envelope
    {
        $event = $this->registration->event;
        $what = match (true) {
            $this->promoted => 'A seat opened up',
            $this->registration->isWaitlisted() => "You're on the waitlist",
            $this->registration->isPending() => 'Registration received',
            default => "You're booked",
        };

        return new Envelope(subject: "{$what}: {$event->title} · {$event->starts_on->format('D j M')} (ref {$this->registration->reference})");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.registrations.confirmation', with: [
            'event' => $this->registration->event,
            'bookingUrl' => route('registrations.show', $this->registration),
        ]);
    }
}
