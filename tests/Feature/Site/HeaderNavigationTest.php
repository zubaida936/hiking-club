<?php

use Illuminate\Support\Facades\Route;

$menu = [
    'Home' => 'home',
    'About' => 'about',
    'Events' => 'events.index',
    'X Events' => 'x-events',
    'Shop' => 'shop.index',
    'Rank System' => 'rank-system',
    'Contact' => 'contact',
    'Difficulty Calculator' => 'guide.difficulty',
    'Equipment Guide' => 'guide.equipment',
    'Guides' => 'guide.articles',
];

it('shows every menu item in the header', function () use ($menu) {
    $response = $this->get('/')->assertOk();

    foreach ($menu as $label => $route) {
        $response->assertSee($label, false)->assertSee('href="'.route($route).'"', false);
    }

    $response->assertSee("Hiker's Guide")
        ->assertSee('Reserve Your Spot')
        ->assertSee('href="'.route('account').'"', false)
        ->assertSee('href="'.route('cart').'"', false);
});

it('has a working page behind every header link', function (string $route) {
    $this->get(route($route))->assertOk()->assertSee('Reserve Your Spot');
})->with([...array_values($menu), 'login', 'cart']);

it('sends guests from the account icon to the member login', function () {
    $this->get(route('account'))->assertRedirect(route('login'));
});

it('marks the current page in the menu', function () {
    $html = $this->get(route('about'))->assertOk()->getContent();

    $current = fn (string $route) => '#href="'.preg_quote(route($route), '#').'"\s+aria-current="page"#';

    expect($html)->toMatch($current('about'))
        ->not->toMatch($current('contact'));
});

it('titles placeholder pages after the section', function () {
    $this->get(route('rank-system'))
        ->assertOk()
        ->assertSee('<title>Rank System · '.config('app.name').'</title>', false)
        ->assertSee('This page is on its way');
});

it('points the call to action at the events page', function () {
    expect(Route::has('events.index'))->toBeTrue();

    $this->get('/')->assertSee('href="'.route('events.index').'"', false);
});
