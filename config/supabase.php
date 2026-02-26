<?php

// IMPORTANT: Do NOT access models/facades here; config is loaded before the app is bootstrapped.
// This file only reads from .env. DB-based overrides are handled at runtime in SupabaseStorageService.

return [
    'url' => rtrim((string) env('SUPABASE_URL', ''), '/'),
    'service_key' => env('SUPABASE_SERVICE_KEY'),
    'bucket' => env('SUPABASE_BUCKET'),
    // in minutes; will be converted to seconds when signing URLs (default 60)
    'signed_url_ttl' => (int) env('SUPABASE_SIGNED_URL_TTL', 60),
];

