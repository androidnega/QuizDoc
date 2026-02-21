<?php

return [
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'timeout' => (int) env('GEMINI_REQUEST_TIMEOUT', 120),
    ],
    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
    ],
    'arkesel' => [
        'api_key' => env('ARKESEL_API_KEY', env('OTP_ARKESEL_API_KEY')),
    ],
    'webpush' => [
        'vapid_public' => env('VAPID_PUBLIC_KEY'),
        'vapid_private' => env('VAPID_PRIVATE_KEY'),
    ],
];
