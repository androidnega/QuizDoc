<?php

use App\Models\Setting;

$supabaseUrl = env('SUPABASE_URL');
$supabaseServiceKey = env('SUPABASE_SERVICE_KEY');
$supabaseBucket = env('SUPABASE_BUCKET');
$signedUrlTtl = env('SUPABASE_SIGNED_URL_TTL'); // minutes

if (class_exists(Setting::class)) {
    $supabaseUrl = Setting::getValue(Setting::KEY_SUPABASE_URL, $supabaseUrl);
    $supabaseServiceKey = Setting::getValue(Setting::KEY_SUPABASE_SERVICE_KEY, $supabaseServiceKey);
    $supabaseBucket = Setting::getValue(Setting::KEY_SUPABASE_BUCKET, $supabaseBucket);
    $signedUrlTtl = Setting::getValue(Setting::KEY_SUPABASE_SIGNED_URL_TTL, $signedUrlTtl ?? '60');
}

return [
    'url' => rtrim((string) $supabaseUrl, '/'),
    'service_key' => $supabaseServiceKey,
    'bucket' => $supabaseBucket,
    // in minutes; will be converted to seconds when signing URLs
    'signed_url_ttl' => (int) ($signedUrlTtl ?: 60),
];

