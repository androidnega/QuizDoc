<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Study guide unlock password
    |--------------------------------------------------------------------------
    | Password required in Settings → Digest to view study guide links.
    | Override via STUDY_GUIDE_UNLOCK_PASSWORD in .env.
    */
    'unlock_password' => env('STUDY_GUIDE_UNLOCK_PASSWORD', 'Atomic2@2020^'),
];
