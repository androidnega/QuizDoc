<?php

return [
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
    ],
    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
    ],
    'arkesel' => [
        'api_key' => env('ARKESEL_API_KEY', env('OTP_ARKESEL_API_KEY')),
    ],
];
