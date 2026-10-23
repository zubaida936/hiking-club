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
}
