<?php

it('tells the app the minimum and latest versions', function () {
    config([
        'mobile.min_version' => '1.2.0',
        'mobile.latest_version' => '1.4.1',
        'mobile.latest_notes' => 'Photo gallery and offline hikes.',
        'mobile.store_url.android' => 'https://play.google.com/store/apps/details?id=club.hiking',
        'mobile.store_url.ios' => '',
    ]);

    $this->getJson(route('api.v1.app-config'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'min_version' => '1.2.0',
            'latest_version' => '1.4.1',
            'latest_notes' => 'Photo gallery and offline hikes.',
            'store_url' => ['android' => 'https://play.google.com/store/apps/details?id=club.hiking', 'ios' => null],
        ]]);
});

it('sends no release notes when none are set', function () {
    config(['mobile.latest_notes' => null]);

    $this->getJson(route('api.v1.app-config'))->assertJsonPath('data.latest_notes', null);
});
