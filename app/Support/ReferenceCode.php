<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Short codes people read out on the phone or WhatsApp (bookings, orders): 8 characters
 * without 0/O or 1/I, unique in the given model's `reference` column.
 */
final class ReferenceCode
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * @param  class-string<Model>  $model
     */
    public static function unique(string $model): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while ($model::query()->where('reference', $code)->exists());

        return $code;
    }
}
