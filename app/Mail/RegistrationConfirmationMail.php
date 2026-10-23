<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly EventRegistration $registration) {}

    public function envelope(): Envelope
    {
        $event = $this->registration->event;
        $what = $this->registration->isPending() ? 'Registration received' : "You're booked";

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
