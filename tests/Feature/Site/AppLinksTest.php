<?php

it('lets the signed Android app open website links', function () {
    config(['mobile.app_links.android_sha256' => ['AB:CD:EF']]);

    $this->get('/.well-known/assetlinks.json')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertExactJson([[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => ['namespace' => 'android_app', 'package_name' => 'club.hiking.app', 'sha256_cert_fingerprints' => ['AB:CD:EF']],
        ]]);
});

it('links no app until the app is signed', function () {
    config(['mobile.app_links.android_sha256' => [], 'mobile.app_links.ios_app_id' => null]);

    $this->get('/.well-known/assetlinks.json')->assertOk()->assertExactJson([]);
    $this->get('/.well-known/apple-app-site-association')->assertOk()->assertExactJson(['applinks' => ['details' => []]]);
});

it('lists the paths the iOS app opens, without the signed CMS links', function () {
    config(['mobile.app_links.ios_app_id' => 'TEAM123.club.hiking.app']);

    $details = $this->get('/.well-known/apple-app-site-association')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->json('applinks.details.0');

    $paths = array_column($details['components'], '/');
    expect($details['appIDs'])->toBe(['TEAM123.club.hiking.app'])
        ->and($paths)->toContain('/events/*', '/bookings/*', '/guides/*')
        ->and($paths)->not->toContain('/activate/*', '/password/*', '/payments/*');
});
