<?php

namespace App\Support;

final class Phone
{
    /**
     * Validation pattern for phone numbers typed by visitors, e.g. "+961 70-123 456".
     */
    public const PATTERN = '/^\+?[0-9\s\-().]{7,20}$/';

    /**
     * Keep a leading "+" and the digits: "+961 70-123 456" -> "+96170123456". Null when nothing is left.
     */
    public static function normalize(?string $phone): ?string
    {
        $phone = preg_replace('/(?!^\+)\D/', '', trim((string) $phone)) ?? '';

        return $phone !== '' ? $phone : null;
    }

    /**
     * The digits to match against phone numbers in a CMS search, e.g. "70 123" -> "70123".
     * Null unless the search looks like part of a phone number (at least 3 digits, no letters),
     * so a reference like "K7F3Q2MZ" or an email doesn't also match phones containing "732".
     */
    public static function searchDigits(string $search): ?string
    {
        if (preg_match('/^\+?[0-9\s\-().]+$/', $search) !== 1) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $search);

        return strlen($digits) >= 3 ? $digits : null;
    }
}
