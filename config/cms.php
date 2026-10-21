<?php

return [

    /*
    | URL prefix of the back-office (CMS).
    */
    'path' => env('CMS_PATH', 'admin'),

    /*
    | Initial super admin created by AdminUserSeeder. Change the password
    | right after the first login in any non-local environment.
    */
    'seed_admin' => [
        'name' => env('ADMIN_NAME', 'Super Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@hiking.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    'per_page' => 20,

];
