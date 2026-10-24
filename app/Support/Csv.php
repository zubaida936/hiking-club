<?php

namespace App\Support;

/**
 * CSV downloads that open cleanly in Excel and can't smuggle in formulas.
 */
final class Csv
{
    /**
     * Writes the rows as UTF-8 with BOM, so Excel shows Arabic names and emoji correctly.
     *
     * @param  resource  $handle
     * @param  iterable<list<string>>  $rows  header row first
     */
    public static function write($handle, iterable $rows): void
    {
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($handle, array_map(self::safeCell(...), $row), escape: '');
        }
    }

    /**
     * Spreadsheet apps run cells starting with = + - @ as formulas; a leading ' keeps them text.
     */
    public static function safeCell(string $value): string
    {
        return $value !== '' && str_contains("=+-@\t\r", $value[0]) ? "'".$value : $value;
    }
}
