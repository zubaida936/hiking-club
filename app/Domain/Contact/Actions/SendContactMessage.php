<?php

namespace App\Domain\Contact\Actions;

use App\Domain\Contact\Data\ContactMessageData;
use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;

/**
 * Emails a contact-form message to the club (SITE_EMAIL), with the sender as
 * Reply-To so the team can answer straight from their inbox. Nothing is stored.
 */
final class SendContactMessage
{
    public function handle(ContactMessageData $data): void
    {
        Mail::to((string) config('site.contact.email'))->queue(new ContactMessageMail($data));
    }
}
