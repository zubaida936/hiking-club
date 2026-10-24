<?php

use App\Support\Phone;

it('normalizes a typed phone number', function () {
    expect(Phone::normalize(' +961 70-123 456 '))->toBe('+96170123456')
        ->and(Phone::normalize('  '))->toBeNull();
});

it('gives the digits of a search that looks like a phone number', function (string $search, string $digits) {
    expect(Phone::searchDigits($search))->toBe($digits);
})->with([
    ['70 123', '70123'],
    ['+961 70-123 456', '96170123456'],
    ['(03) 123', '03123'],
]);

it('ignores searches that are not phone numbers', function (string $search) {
    expect(Phone::searchDigits($search))->toBeNull();
})->with(['K7F3Q2MZ', 'rima2024@example.com', 'Rima', '70', '']);
