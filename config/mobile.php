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

];
