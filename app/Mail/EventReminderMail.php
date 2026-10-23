<?php

namespace App\Mail;

use App\Models\EventRegistration;
use App\Support\WhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "See you tomorrow": pickup, what to bring, payment due and the latest organizer updates.
 */
class EventReminderMail extends Mailable implements ShouldQueue
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
        $pickup = $this->registration->pickup_point ? " · pickup {$this->registration->pickupTimeShort()} {$this->registration->pickup_point}" : '';

        return new Envelope(subject: "Reminder: {$event->title} on {$event->starts_on->format('D j M')}{$pickup}");
    }

    public function content(): Content
    {
        $event = $this->registration->event;

        return new Content(markdown: 'mail.registrations.reminder', with: [
            'event' => $event,
            'updates' => $event->updates()->limit(3)->get(),
            'bookingUrl' => route('registrations.show', $this->registration),
            'whatsappUrl' => WhatsApp::link("Hi! About my booking {$this->registration->reference} for {$event->title}:"),
        ]);
    }
}
