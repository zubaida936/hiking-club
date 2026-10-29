<?php

it('tells the app the minimum and latest versions', function () {
    config([
        'mobile.min_version' => '1.2.0',
        'mobile.latest_version' => '1.4.1',
        'mobile.store_url.android' => 'https://play.google.com/store/apps/details?id=club.hiking',
        'mobile.store_url.ios' => '',
    ]);

    $this->getJson(route('api.v1.app-config'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'min_version' => '1.2.0',
            'latest_version' => '1.4.1',
            'store_url' => ['android' => 'https://play.google.com/store/apps/details?id=club.hiking', 'ios' => null],
        ]]);
});
