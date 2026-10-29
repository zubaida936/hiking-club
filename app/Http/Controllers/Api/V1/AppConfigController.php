<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

class AppConfigController extends Controller
{
    /**
     * App configuration.
     *
     * Asked by the app when it starts. When the installed version is below `min_version`, the app shows
     * only an "update the app" screen (with `store_url` for its platform); below `latest_version` it may
     * suggest updating. Set in `config/mobile.php` (`MOBILE_*` in `.env`), no release needed.
     *
     * @unauthenticated
     *
     * @return array{data: array{min_version: string, latest_version: string, store_url: array{android: ?string, ios: ?string}}}
     */
    public function __invoke(): array
    {
        return ['data' => [
            'min_version' => (string) config('mobile.min_version'),
            'latest_version' => (string) config('mobile.latest_version'),
            'store_url' => [
                'android' => config('mobile.store_url.android') ?: null,
                'ios' => config('mobile.store_url.ios') ?: null,
            ],
        ]];
    }
}
