<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure Supabase-related setting keys exist (values can be null).
        $keys = [
            Setting::KEY_SUPABASE_URL,
            Setting::KEY_SUPABASE_SERVICE_KEY,
            Setting::KEY_SUPABASE_BUCKET,
            Setting::KEY_SUPABASE_SIGNED_URL_TTL,
        ];

        foreach ($keys as $key) {
            if (!Setting::where('key', $key)->exists()) {
                Setting::create([
                    'key' => $key,
                    'value' => $key === Setting::KEY_SUPABASE_SIGNED_URL_TTL ? '60' : null,
                ]);
            }
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            Setting::KEY_SUPABASE_URL,
            Setting::KEY_SUPABASE_SERVICE_KEY,
            Setting::KEY_SUPABASE_BUCKET,
            Setting::KEY_SUPABASE_SIGNED_URL_TTL,
        ])->delete();
    }
};

