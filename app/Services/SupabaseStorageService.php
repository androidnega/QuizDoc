<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    public static function isConfigured(): bool
    {
        $url = static::getUrl();
        $key = static::getServiceKey();
        $bucket = static::getBucket();

        return (bool) ($url && $key && $bucket);
    }

    /**
     * Upload a document (pdf, doc, docx) to Supabase Storage.
     *
     * @return array{success: bool, path?: string, message?: string}
     */
    public static function uploadDocument(UploadedFile $file, string $prefix = 'student-documents'): array
    {
        if (!static::isConfigured()) {
            return [
                'success' => false,
                'message' => 'Supabase Storage is not configured. Set URL, service key, and bucket in Admin Settings.',
            ];
        }

        $baseUrl = rtrim(static::getUrl(), '/');
        $serviceKey = static::getServiceKey();
        $bucket = static::getBucket();

        $extension = strtolower($file->getClientOriginalExtension());
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        if ($safeName === '') {
            $safeName = 'document';
        }

        $objectPath = trim($prefix . '/' . date('Y/m/d') . '/' . $safeName . '-' . Str::random(8) . '.' . $extension, '/');

        $endpoint = $baseUrl . '/storage/v1/object/' . rawurlencode($bucket);

        try {
            $response = Http::withHeaders([
                    'apikey' => $serviceKey,
                    'Authorization' => 'Bearer ' . $serviceKey,
                ])
                ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
                ->post($endpoint, [
                    'name' => $objectPath,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Supabase upload failed: HTTP ' . $response->status(),
                ];
            }

            return [
                'success' => true,
                'path' => $objectPath,
            ];
        } catch (\Throwable $e) {
            \Log::error('Supabase upload exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Supabase upload error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a signed download URL for an object path.
     *
     * @return array{success: bool, url?: string, message?: string}
     */
    public static function createSignedUrl(string $path): array
    {
        if (!static::isConfigured()) {
            return [
                'success' => false,
                'message' => 'Supabase Storage is not configured.',
            ];
        }

        $baseUrl = rtrim(static::getUrl(), '/');
        $serviceKey = static::getServiceKey();
        $bucket = static::getBucket();
        $ttlMinutes = static::getSignedUrlTtlMinutes();
        $ttlSeconds = max(60, $ttlMinutes * 60);

        $path = ltrim($path, '/');
        $endpoint = $baseUrl . '/storage/v1/object/sign/' . rawurlencode($bucket) . '/' . $path;

        try {
            $response = Http::withHeaders([
                    'apikey' => $serviceKey,
                    'Authorization' => 'Bearer ' . $serviceKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'expiresIn' => $ttlSeconds,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Supabase sign URL failed: HTTP ' . $response->status(),
                ];
            }

            $data = $response->json();
            $signedPath = $data['signedURL'] ?? null;
            if (!$signedPath) {
                return [
                    'success' => false,
                    'message' => 'Supabase did not return a signedURL.',
                ];
            }

            $fullUrl = $baseUrl . $signedPath;

            return [
                'success' => true,
                'url' => $fullUrl,
            ];
        } catch (\Throwable $e) {
            \Log::error('Supabase sign URL exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Supabase sign URL error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Supabase connectivity and bucket access using current config/DB settings.
     *
     * @return array{success: bool, message: string, detail?: string}
     */
    public static function testConnection(): array
    {
        if (!static::isConfigured()) {
            return [
                'success' => false,
                'message' => 'Supabase is not configured. Set URL, service key, and bucket in Admin Settings.',
            ];
        }

        $baseUrl = rtrim(static::getUrl(), '/');
        $serviceKey = static::getServiceKey();
        $bucket = static::getBucket();

        $endpoint = $baseUrl . '/storage/v1/bucket/' . rawurlencode($bucket);

        try {
            $response = Http::withHeaders([
                'apikey' => $serviceKey,
                'Authorization' => 'Bearer ' . $serviceKey,
            ])->get($endpoint);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Supabase connection OK. Bucket is reachable.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Supabase request failed.',
                'detail' => 'HTTP ' . $response->status() . ': ' . $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Supabase request error.',
                'detail' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a document from Supabase Storage by object path.
     */
    public static function deleteDocument(string $path): bool
    {
        if (!static::isConfigured() || $path === '') {
            return false;
        }

        $baseUrl = rtrim(static::getUrl(), '/');
        $serviceKey = static::getServiceKey();
        $bucket = static::getBucket();
        $path = ltrim($path, '/');

        $endpoint = $baseUrl . '/storage/v1/object/' . rawurlencode($bucket) . '/' . $path;

        try {
            $response = Http::withHeaders([
                'apikey' => $serviceKey,
                'Authorization' => 'Bearer ' . $serviceKey,
            ])->delete($endpoint);

            return $response->successful() || $response->status() === 404;
        } catch (\Throwable $e) {
            \Log::warning('Supabase delete exception', [
                'message' => $e->getMessage(),
                'path' => $path,
            ]);
            return false;
        }
    }


    private static function getUrl(): string
    {
        $db = class_exists(Setting::class)
            ? Setting::getValue(Setting::KEY_SUPABASE_URL)
            : null;

        return $db !== null && $db !== ''
            ? rtrim($db, '/')
            : (string) config('supabase.url');
    }

    private static function getServiceKey(): string
    {
        $db = class_exists(Setting::class)
            ? Setting::getValue(Setting::KEY_SUPABASE_SERVICE_KEY)
            : null;

        return $db !== null && $db !== ''
            ? $db
            : (string) config('supabase.service_key');
    }

    private static function getBucket(): string
    {
        $db = class_exists(Setting::class)
            ? Setting::getValue(Setting::KEY_SUPABASE_BUCKET)
            : null;

        if ($db !== null && $db !== '') {
            return $db;
        }

        return (string) config('supabase.bucket');
    }

    private static function getSignedUrlTtlMinutes(): int
    {
        $db = class_exists(Setting::class)
            ? Setting::getValue(Setting::KEY_SUPABASE_SIGNED_URL_TTL)
            : null;

        if ($db !== null && trim($db) !== '') {
            return max(1, min(1440, (int) $db));
        }

        return (int) (config('supabase.signed_url_ttl') ?: 60);
    }
}

