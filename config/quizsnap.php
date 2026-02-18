<?php

return [
    /*
    | Login token (OTP) validity in seconds. Tokens expire after this period (e.g. 14 days).
    */
    'otp_ttl_seconds' => (int) env('QUIZSNAP_OTP_TTL_SECONDS', 14 * 86400),

    /*
    | Staff (admin/examiner) credentials are stored in the `users` table only.
    | To create the first accounts, set ADMIN_USERNAME, ADMIN_PASSWORD (and optionally
    | EXAMINER_*) in .env and run: php artisan db:seed
    | Set ADMIN_* and EXAMINER_* in .env, then run: php artisan db:seed
    */

    'ai' => [
        'max_generation_per_quiz' => (int) env('QUIZSNAP_AI_MAX_PER_QUIZ', 100),
    ],
];
