<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'https://studenthub-frontend-alpha.vercel.app',
        'https://studenthub-frontend-vhpt-al6vtbwus-alexs-projects-3dc248bb.vercel.app',
        'https://stdh.vercel.app',
    ],

    'allowed_origins_patterns' => [
        '#^https://studenthub-frontend-vhpt-[a-z0-9-]+-alexs-projects-3dc248bb\.vercel\.app$#',
        '#^https://studenthub-frontend-.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
