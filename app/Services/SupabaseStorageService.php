<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    public static function isConfigured(): bool
    {
        $url = config('supabase.url');
        $key = config('supabase.service_key');
        $bucket = config('supabase.bucket');

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

        $baseUrl = rtrim((string) config('supabase.url'), '/');
        $serviceKey = (string) config('supabase.service_key');
        $bucket = (string) config('supabase.bucket');

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

        $baseUrl = rtrim((string) config('supabase.url'), '/');
        $serviceKey = (string) config('supabase.service_key');
        $bucket = (string) config('supabase.bucket');
        $ttlMinutes = (int) (config('supabase.signed_url_ttl') ?: 60);
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
}

