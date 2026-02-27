<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WebAuthn Relying Party (student passkeys only)
    |--------------------------------------------------------------------------
    */
    'rp_name' => env('WEBAUTHN_RP_NAME', env('APP_NAME', 'QuizSnap')),
    'rp_id' => env('WEBAUTHN_RP_ID', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'),
    'timeout' => (int) env('WEBAUTHN_TIMEOUT', 60),
    'use_base64url' => true,
];
