<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordResetCodeMail extends Mailable
{
    public function __construct(
        public readonly User $user,
        public readonly string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your password reset code: {$this->code}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.members.reset-code', with: [
            'minutes' => config('members.code_ttl_minutes'),
        ]);
    }
}
