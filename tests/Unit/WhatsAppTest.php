<?php

use App\Support\WhatsApp;

it('links to the configured number', function () {
    config()->set('site.contact.whatsapp', '96170123456');

    expect(WhatsApp::link())->toBe('https://wa.me/96170123456');
});

it('strips formatting from the number', function () {
    config()->set('site.contact.whatsapp', '+961 70-123 456');

    expect(WhatsApp::link())->toBe('https://wa.me/96170123456');
});

it('pre-fills an url-encoded message', function () {
    config()->set('site.contact.whatsapp', '96170123456');

    expect(WhatsApp::link('Hi! Seats for "Qadisha" & more?'))
        ->toBe('https://wa.me/96170123456?text=Hi%21%20Seats%20for%20%22Qadisha%22%20%26%20more%3F');
});

it('returns null without a number', function (?string $number) {
    config()->set('site.contact.whatsapp', $number);

    expect(WhatsApp::link('Hi'))->toBeNull();
})->with(['empty' => '', 'null' => null, 'no digits' => 'none']);
