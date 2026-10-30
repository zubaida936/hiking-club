<?php

/*
| The mobile app (mobile/, Expo). The app asks GET /api/v1/app-config when it starts:
| below `min_version` it shows "Please update" and nothing else (raise it when an API change
| would break older installed apps); below `latest_version` it may suggest updating.
| Versions are the app's version from mobile/app.json ("1.2.0").
*/

return [

    'min_version' => env('MOBILE_MIN_VERSION', '1.0.0'),

    'latest_version' => env('MOBILE_LATEST_VERSION', '1.0.0'),

    // Store page the update screen opens. Empty until the app is published.
    'store_url' => [
        'android' => env('MOBILE_ANDROID_STORE_URL'),
        'ios' => env('MOBILE_IOS_STORE_URL'),
    ],

    /*
    | App Links (Android) and Universal Links (iOS): website links open the installed app. Served at
    | /.well-known/assetlinks.json and /.well-known/apple-app-site-association. The app declares the same paths
    | (mobile/app.config.ts, built with APP_LINKS_HOST = this site's domain): keep both lists in step.
    | Empty fingerprints / app id: the files are served but no app is linked (until the app is signed).
    */
    'app_links' => [
        'android_package' => env('MOBILE_ANDROID_PACKAGE', 'club.hiking.app'),
        // SHA-256 certificate fingerprints, comma separated (EAS credentials or Play Console → App signing).
        'android_sha256' => array_values(array_filter(array_map('trim', explode(',', (string) env('MOBILE_ANDROID_SHA256', ''))))),
        // "TEAMID.bundle.identifier" from the Apple developer account.
        'ios_app_id' => env('MOBILE_IOS_APP_ID'),
        // Website paths the app has a screen for ("*" = anything below). The CMS's signed links (invite,
        // set password) and payment pages stay on the website.
        'paths' => [
            '/', '/events', '/events/*', '/bookings/*', '/shop', '/shop/*', '/cart', '/checkout', '/orders/*',
            '/x-events', '/x-events/*', '/difficulty-calculator', '/rank-system', '/leaderboard', '/guides', '/guides/*',
            '/gallery', '/gallery/*', '/equipment', '/contact', '/about', '/account', '/account/*', '/login', '/activate',
            '/forgot-password',
        ],
    ],

];
