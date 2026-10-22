<?php

function footerHtml(string $uri = '/'): string
{
    $html = test()->get($uri)->assertOk()->getContent();

    return substr($html, strpos($html, '<footer'));
}

it('shows the contact details', function () {
    config()->set('site.contact', [
        'address' => 'Hamra Street, Beirut',
        'phone' => '+961 70 123 456',
        'whatsapp' => '96170123456',
        'email' => 'club@example.com',
    ]);

    expect(footerHtml())
        ->toContain('Hamra Street, Beirut')
        ->toContain('href="tel:+96170123456"')
        ->toContain('+961 70 123 456')
        ->toContain('href="mailto:club@example.com"');
});

it('links every social account', function () {
    config()->set('site.social', ['facebook' => 'fb.club', 'instagram' => 'ig.club', 'tiktok' => 'tt.club', 'youtube' => 'yt.club']);
    config()->set('site.contact.whatsapp', '96170123456');

    expect(footerHtml())
        ->toContain('href="https://www.facebook.com/fb.club"')
        ->toContain('href="https://www.instagram.com/ig.club"')
        ->toContain('href="https://www.tiktok.com/@tt.club"')
        ->toContain('href="https://www.youtube.com/@yt.club"')
        ->toContain('href="https://wa.me/96170123456"')
        ->toContain('aria-label="TikTok"')
        ->toContain('aria-label="YouTube"');
});

it('hides social accounts that are not set', function () {
    config()->set('site.social.tiktok', '');
    config()->set('site.social.youtube', null);

    expect(footerHtml())
        ->not->toContain('tiktok.com')
        ->not->toContain('youtube.com')
        ->toContain('instagram.com');
});

it('has a site map built from the main menu plus support links', function () {
    $footer = footerHtml();

    foreach (['home', 'events.index', 'x-events', 'shop.index', 'rank-system', 'guide.difficulty', 'guide.equipment', 'guide.articles', 'about', 'contact', 'legal.privacy'] as $route) {
        expect($footer)->toContain('href="'.route($route).'"');
    }

    expect($footer)->toContain('Explore')->toContain('Hiker&#039;s Guide')->toContain('Support')->toContain('Privacy Policy');
});

it('has a working privacy policy page', function () {
    $this->get(route('legal.privacy'))->assertOk()->assertSee('Privacy Policy');
});

it('shows the copyright with the current year', function () {
    expect(footerHtml())->toContain('&copy; '.now()->year.' '.config('app.name').'. All rights reserved.');
});

it('appears on every public page', function () {
    expect(footerHtml(route('about')))->toContain('All rights reserved.');
});
