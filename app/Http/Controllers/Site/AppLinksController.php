<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * The files phones check before opening website links in the installed app (config/mobile.php → app_links).
 */
class AppLinksController extends Controller
{
    /** Android App Links: which signed app may open this site's links. */
    public function android(): JsonResponse
    {
        /** @var list<string> $fingerprints */
        $fingerprints = (array) config('mobile.app_links.android_sha256');

        return response()->json($fingerprints === [] ? [] : [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => (string) config('mobile.app_links.android_package'),
                'sha256_cert_fingerprints' => $fingerprints,
            ],
        ]]);
    }

    /** iOS Universal Links: the app and the paths it opens (served without extension, as JSON). */
    public function apple(): JsonResponse
    {
        $appId = config('mobile.app_links.ios_app_id');

        $components = [];
        foreach ((array) config('mobile.app_links.paths') as $path) {
            $components[] = ['/' => (string) $path];
        }

        return response()->json(['applinks' => ['details' => $appId ? [['appIDs' => [(string) $appId], 'components' => $components]] : []]]);
    }
}
