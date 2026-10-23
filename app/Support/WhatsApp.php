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

    /**
     * wa.me link to message someone else (e.g. an organizer writing to a hiker), with a pre-filled text.
     */
    public static function to(string $phone, string $message): string
    {
        return 'https://wa.me/'.preg_replace('/\D/', '', $phone).'?text='.rawurlencode($message);
    }
}
