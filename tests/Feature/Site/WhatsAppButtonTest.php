<?php

use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    config()->set('site.contact.whatsapp', '96170123456');
    config()->set('site.contact.whatsapp_message', 'Hi! I have a question about your hikes.');
});

it('shows the floating button with the default message on every public page', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('aria-label="Chat with us on WhatsApp"', false)
        ->assertSee('href="https://wa.me/96170123456?text=Hi%21%20I%20have%20a%20question%20about%20your%20hikes."', false);
})->with(['home', 'about', 'events.index', 'legal.privacy']);

it('opens in a new tab', function () {
    $this->get('/')->assertSee('target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp"', false);
});

it('lets a page pre-fill its own message', function () {
    $html = Blade::render('<x-site.layout title="Qadisha" whatsapp="Hi! A question about Qadisha Valley Descent">Body</x-site.layout>');

    expect($html)
        ->toContain('https://wa.me/96170123456?text=Hi%21%20A%20question%20about%20Qadisha%20Valley%20Descent')
        ->not->toContain('your%20hikes');
});

it('hides every WhatsApp link when no number is set', function () {
    config()->set('site.contact.whatsapp', '');

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Chat with us on WhatsApp')
        ->assertDontSee('wa.me', false)
        ->assertDontSee('aria-label="WhatsApp"', false);
});
