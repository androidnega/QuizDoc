<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloudinaryService
{
    /**
     * Check if Cloudinary is configured (cloud name + api key + secret).
     */
    public static function isConfigured(): bool
    {
        $cloud = trim(Setting::getValue(Setting::KEY_CLOUDINARY_CLOUD_NAME) ?? '');
        $key = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY) ?? '');
        $secret = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_SECRET) ?? '');
        return $cloud !== '' && $key !== '' && $secret !== '';
    }

    /**
     * Upload image to Cloudinary. Input can be base64 data URL (data:image/...;base64,...) or raw file path.
     * Returns the secure URL on success, or null on failure.
     * Uses lightweight options: quality_auto, fetch_format auto for smaller files.
     */
    public static function upload(string $imageInput, string $publicIdPrefix = 'quizsnap'): ?string
    {
        if (!static::isConfigured()) {
            return null;
        }
        $cloudName = trim(Setting::getValue(Setting::KEY_CLOUDINARY_CLOUD_NAME) ?? '');
        $apiKey = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY) ?? '');
        $apiSecret = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_SECRET) ?? '');
        $folder = trim(Setting::getValue(Setting::KEY_CLOUDINARY_FOLDER, 'quizsnap') ?? 'quizsnap');
        if ($folder === '') {
            $folder = 'quizsnap';
        }

        $timestamp = (string) time();

        // Signature must include ONLY upload parameters (not transformation parameters like quality/fetch_format)
        $paramsToSign = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];
        ksort($paramsToSign);
        $strToSign = implode('&', array_map(fn ($k, $v) => $k . '=' . $v, array_keys($paramsToSign), $paramsToSign));
        $signature = sha1($strToSign . $apiSecret);

        $url = 'https://api.cloudinary.com/v1_1/' . $cloudName . '/image/upload';

        $multipart = [
            ['name' => 'file', 'contents' => $imageInput],
            ['name' => 'api_key', 'contents' => $apiKey],
            ['name' => 'timestamp', 'contents' => $timestamp],
            ['name' => 'signature', 'contents' => $signature],
            ['name' => 'quality', 'contents' => 'auto'],
            ['name' => 'fetch_format', 'contents' => 'auto'],
        ];
        if ($folder !== '') {
            $multipart[] = ['name' => 'folder', 'contents' => $folder];
        }

        try {
            $response = Http::timeout(30)->asMultipart()->post($url, $multipart);
            if ($response->successful()) {
                $data = $response->json();
                return $data['secure_url'] ?? null;
            }
            // Log the failure response for debugging
            \Log::error('Cloudinary upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Cloudinary upload exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);
        }
        return null;
    }

    /**
     * Upload from base64 data URL (e.g. from canvas/camera). Returns secure URL or null.
     */
    public static function uploadFromDataUrl(string $dataUrl, string $publicIdPrefix = 'quizsnap'): ?string
    {
        if (!Str::startsWith($dataUrl, 'data:image')) {
            return null;
        }
        return static::upload($dataUrl, $publicIdPrefix);
    }

    /**
     * Upload from uploaded file (e.g. form file input). Uploads directly to Cloudinary. Returns secure URL or null.
     */
    public static function uploadFromFile(UploadedFile $file): ?string
    {
        if (!static::isConfigured()) {
            return null;
        }
        $cloudName = trim(Setting::getValue(Setting::KEY_CLOUDINARY_CLOUD_NAME) ?? '');
        $apiKey = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY) ?? '');
        $apiSecret = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_SECRET) ?? '');
        $folder = trim(Setting::getValue(Setting::KEY_CLOUDINARY_FOLDER, 'quizsnap') ?? 'quizsnap');
        if ($folder === '') {
            $folder = 'quizsnap';
        }
        $timestamp = (string) time();
        $paramsToSign = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];
        ksort($paramsToSign);
        $strToSign = implode('&', array_map(fn ($k, $v) => $k . '=' . $v, array_keys($paramsToSign), $paramsToSign));
        $signature = sha1($strToSign . $apiSecret);
        $url = 'https://api.cloudinary.com/v1_1/' . $cloudName . '/image/upload';
        $multipart = [
            ['name' => 'file', 'contents' => fopen($file->getRealPath(), 'r'), 'filename' => $file->getClientOriginalName()],
            ['name' => 'api_key', 'contents' => $apiKey],
            ['name' => 'timestamp', 'contents' => $timestamp],
            ['name' => 'signature', 'contents' => $signature],
            ['name' => 'quality', 'contents' => 'auto'],
            ['name' => 'fetch_format', 'contents' => 'auto'],
        ];
        if ($folder !== '') {
            $multipart[] = ['name' => 'folder', 'contents' => $folder];
        }
        try {
            $response = Http::timeout(30)->asMultipart()->post($url, $multipart);
            if ($response->successful()) {
                $data = $response->json();
                return $data['secure_url'] ?? null;
            }
            \Log::error('Cloudinary upload failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            \Log::error('Cloudinary upload exception', ['message' => $e->getMessage()]);
            report($e);
        }
        return null;
    }

    /**
     * Upload raw file (PDF, DOCX, TXT) to Cloudinary. Stored in folder/scripts.
     * Returns ['url' => secure_url, 'public_id' => public_id] or null on failure.
     */
    public static function uploadRawFromFile(UploadedFile $file, string $subfolder = 'scripts'): ?array
    {
        if (!static::isConfigured()) {
            return null;
        }
        $cloudName = trim(Setting::getValue(Setting::KEY_CLOUDINARY_CLOUD_NAME) ?? '');
        $apiKey = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY) ?? '');
        $apiSecret = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_SECRET) ?? '');
        $baseFolder = trim(Setting::getValue(Setting::KEY_CLOUDINARY_FOLDER, 'quizsnap') ?? 'quizsnap');
        $folder = $baseFolder === '' ? $subfolder : $baseFolder . '/' . $subfolder;
        $timestamp = (string) time();
        $paramsToSign = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];
        ksort($paramsToSign);
        $strToSign = implode('&', array_map(fn ($k, $v) => $k . '=' . $v, array_keys($paramsToSign), $paramsToSign));
        $signature = sha1($strToSign . $apiSecret);
        $url = 'https://api.cloudinary.com/v1_1/' . $cloudName . '/raw/upload';
        $multipart = [
            ['name' => 'file', 'contents' => fopen($file->getRealPath(), 'r'), 'filename' => $file->getClientOriginalName()],
            ['name' => 'api_key', 'contents' => $apiKey],
            ['name' => 'timestamp', 'contents' => $timestamp],
            ['name' => 'signature', 'contents' => $signature],
            ['name' => 'folder', 'contents' => $folder],
        ];
        try {
            $response = Http::timeout(60)->asMultipart()->post($url, $multipart);
            if ($response->successful()) {
                $data = $response->json();
                $secureUrl = $data['secure_url'] ?? null;
                $publicId = $data['public_id'] ?? null;
                if ($secureUrl && $publicId !== null) {
                    return ['url' => $secureUrl, 'public_id' => $publicId];
                }
            }
            \Log::error('Cloudinary raw upload failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            \Log::error('Cloudinary raw upload exception', ['message' => $e->getMessage()]);
            report($e);
        }
        return null;
    }

    /**
     * Delete raw asset by public_id (e.g. script when quiz is deleted). Returns true if deleted or not found.
     */
    public static function deleteRawByPublicId(string $publicId): bool
    {
        if (!static::isConfigured() || $publicId === '') {
            return true;
        }
        $cloudName = trim(Setting::getValue(Setting::KEY_CLOUDINARY_CLOUD_NAME) ?? '');
        $apiKey = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY) ?? '');
        $apiSecret = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_SECRET) ?? '');
        $timestamp = (string) time();
        $paramsToSign = ['public_id' => $publicId, 'timestamp' => $timestamp];
        ksort($paramsToSign);
        $strToSign = implode('&', array_map(fn ($k, $v) => $k . '=' . $v, array_keys($paramsToSign), $paramsToSign));
        $signature = sha1($strToSign . $apiSecret);
        $url = 'https://api.cloudinary.com/v1_1/' . $cloudName . '/raw/destroy';
        try {
            $response = Http::asForm()->post($url, [
                'public_id' => $publicId,
                'api_key' => $apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);
            return $response->successful() || $response->status() === 404;
        } catch (\Throwable $e) {
            \Log::warning('Cloudinary raw delete failed', ['public_id' => $publicId, 'message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Test connection: try a tiny upload (1x1 pixel PNG) and return success/message with detailed error.
     */
    public static function testConnection(): array
    {
        if (!static::isConfigured()) {
            return ['success' => false, 'message' => 'Cloudinary is not configured. Set cloud name, API key, and API secret.'];
        }
        
        $cloudName = trim(Setting::getValue(Setting::KEY_CLOUDINARY_CLOUD_NAME) ?? '');
        $apiKey = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY) ?? '');
        $apiSecret = trim(Setting::getValue(Setting::KEY_CLOUDINARY_API_SECRET) ?? '');
        $folder = trim(Setting::getValue(Setting::KEY_CLOUDINARY_FOLDER, 'quizsnap') ?? 'quizsnap');
        if ($folder === '') {
            $folder = 'quizsnap';
        }
        
        $onePixelPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $timestamp = (string) time();
        
        // Signature must include ONLY upload parameters (not transformation parameters like quality/fetch_format)
        $paramsToSign = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];
        ksort($paramsToSign);
        $strToSign = implode('&', array_map(fn ($k, $v) => $k . '=' . $v, array_keys($paramsToSign), $paramsToSign));
        $signature = sha1($strToSign . $apiSecret);
        
        $url = 'https://api.cloudinary.com/v1_1/' . $cloudName . '/image/upload';
        
        $multipart = [
            ['name' => 'file', 'contents' => $onePixelPng],
            ['name' => 'api_key', 'contents' => $apiKey],
            ['name' => 'timestamp', 'contents' => $timestamp],
            ['name' => 'signature', 'contents' => $signature],
            ['name' => 'quality', 'contents' => 'auto'],
            ['name' => 'fetch_format', 'contents' => 'auto'],
            ['name' => 'folder', 'contents' => $folder],
        ];
        
        try {
            $response = Http::timeout(30)->asMultipart()->post($url, $multipart);
            if ($response->successful()) {
                $data = $response->json();
                $uploadedUrl = $data['secure_url'] ?? null;
                if ($uploadedUrl) {
                    return ['success' => true, 'message' => 'Cloudinary connection OK. Test image uploaded.', 'url' => $uploadedUrl];
                }
            }
            // Return detailed error from Cloudinary
            $errorBody = $response->json();
            $errorMsg = $errorBody['error']['message'] ?? $response->body();
            return [
                'success' => false, 
                'message' => 'Cloudinary upload failed.',
                'detail' => 'HTTP ' . $response->status() . ': ' . $errorMsg
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Cloudinary request failed.',
                'detail' => $e->getMessage()
            ];
        }
    }
}
