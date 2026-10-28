<?php

it('builds links from the proxy forwarded host and scheme', function () {
    $this->withoutVite()->withHeaders([
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'demo.example.com',
        'X-Forwarded-Port' => '443',
    ])->get('/')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://demo.example.com">', false)
        ->assertSee('content="https://demo.example.com/images/og-default.jpg"', false)
        ->assertDontSee('href="'.config('app.url'), false);
});

it('uses the request host when there is no proxy', function () {
    $this->withoutVite()->get('/')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
        ->assertDontSee('https://demo.example.com', false);
});
