<?php

return [

    'wipe_api' => [
        'base_url' => env('WIPE_API_BASE_URL', 'https://stellerphonewipeapiprod.azurewebsites.net/api/'),
        'username' => env('WIPE_API_USERNAME', env('APPSETTING_API_USERNAME_STELLER_PHONE_WIPE_API')),
        'password' => env('WIPE_API_PASSWORD', env('APPSETTING_API_PASSWORD_STELLER_PHONE_WIPE_API')),
        'token_lookup_method' => strtolower(env('WIPE_API_TOKEN_LOOKUP_METHOD', 'get')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
