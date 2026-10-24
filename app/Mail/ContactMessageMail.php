<?php

namespace App\Mail;

use App\Domain\Contact\Data\ContactMessageData;
use App\Support\WhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

/**
 * A contact-form message for the club; replying answers the sender.
 */
class ContactMessageMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ContactMessageData $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->data->email, $this->data->name)],
            subject: 'Website message from '.Str::limit($this->data->name, 60),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact.message', with: [
            'whatsapp' => $this->data->phone ? WhatsApp::to($this->data->phone, "Hi {$this->data->name}, thanks for your message!") : null,
        ]);
    }
}
