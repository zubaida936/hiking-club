<?php

namespace App\Support;

final class WhatsApp
{
    /**
     * wa.me chat link to the club's number (config site.contact.whatsapp), optionally
     * with a pre-filled message. Null when no number is configured.
     */
    public static function link(?string $message = null): ?string
    {
        $number = preg_replace('/\D/', '', (string) config('site.contact.whatsapp'));

        if ($number === null || $number === '') {
            return null;
        }

        return "https://wa.me/{$number}".($message !== null && $message !== '' ? '?text='.rawurlencode($message) : '');
    }
}
