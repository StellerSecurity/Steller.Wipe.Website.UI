<?php

return [
    'session_max_age_seconds' => (int) env('WIPE_SESSION_MAX_AGE_SECONDS', 900),
    'confirmation_max_age_seconds' => (int) env('WIPE_CONFIRMATION_MAX_AGE_SECONDS', 300),

    'azure_front_door' => [
        'required' => filter_var(env('AZURE_FRONT_DOOR_REQUIRED', false), FILTER_VALIDATE_BOOL),
        'id' => env('AZURE_FRONT_DOOR_ID'),
    ],

    'headers' => [
        'content_security_policy' => env(
            'WIPE_CONTENT_SECURITY_POLICY',
            "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; font-src 'self' https://fonts.bunny.net; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'"
        ),
    ],
];
