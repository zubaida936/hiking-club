<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmSubscriptionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Subscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your Next Destination Alerts');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.newsletter.confirm', with: [
            'confirmUrl' => route('newsletter.confirm', $this->subscriber->token),
        ]);
    }
}
