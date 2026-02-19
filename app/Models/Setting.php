<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key. Cached for the request. Decrypts if key is sensitive.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $cacheKey = 'setting:' . $key;
        $value = Cache::remember($cacheKey, 3600, function () use ($key) {
            $row = static::where('key', $key)->first();
            return $row?->value;
        });
        if ($value === null) {
            return $default;
        }
        if (in_array($key, self::ENCRYPTED_KEYS, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException $e) {
                \Illuminate\Support\Facades\Log::warning('Setting decryption failed (wrong APP_KEY?). Key: ' . $key);
                return $default;
            }
        }
        return $value;
    }

    /**
     * Set a setting value by key. Encrypts if key is sensitive.
     */
    public static function setValue(string $key, ?string $value): void
    {
        $stored = $value;
        if ($value !== null && $value !== '' && in_array($key, self::ENCRYPTED_KEYS, true)) {
            $stored = Crypt::encryptString($value);
        }
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored]
        );
        Cache::forget('setting:' . $key);
    }

    public const KEY_OPENAI_API = 'openai_api_key';
    public const KEY_GEMINI_API = 'gemini_api_key';
    public const KEY_DEEPSEEK_API = 'deepseek_api_key';

    /** General */
    public const KEY_APP_NAME = 'app_name';
    public const KEY_APP_TIMEZONE = 'app_timezone';
    /** Footer copyright text shown on Settings page. Use {year} for current year. */
    public const KEY_FOOTER_COPYRIGHT = 'footer_copyright';
    /** Mobile landing hero: 1 = show on phones, 0 = hide (Super Admin). */
    public const KEY_LANDING_HERO_ENABLED = 'landing_hero_enabled';
    /** Mobile landing hero image URL (Super Admin). Shown on phone only when enabled. Can be set via URL or local upload (stored on Cloudinary). */
    public const KEY_LANDING_HERO_IMAGE = 'landing_hero_image';
    public const KEY_INSTITUTION_NAME = 'institution_name';
    public const KEY_INSTITUTION_LOGO = 'institution_logo';

    /** Cloudinary (proctoring / result photos) */
    public const KEY_CLOUDINARY_CLOUD_NAME = 'cloudinary_cloud_name';
    public const KEY_CLOUDINARY_API_KEY = 'cloudinary_api_key';
    public const KEY_CLOUDINARY_API_SECRET = 'cloudinary_api_secret';
    public const KEY_CLOUDINARY_FOLDER = 'cloudinary_folder';

    /** Mail */
    public const KEY_MAIL_MAILER = 'mail_mailer';
    public const KEY_MAIL_HOST = 'mail_host';
    public const KEY_MAIL_PORT = 'mail_port';
    public const KEY_MAIL_USERNAME = 'mail_username';
    public const KEY_MAIL_PASSWORD = 'mail_password';
    public const KEY_MAIL_ENCRYPTION = 'mail_encryption';
    public const KEY_MAIL_FROM_ADDRESS = 'mail_from_address';
    public const KEY_MAIL_FROM_NAME = 'mail_from_name';

    /** Notifications: send email when a student submits a quiz (result ready). */
    public const KEY_NOTIFY_RESULT_READY = 'notify_result_ready';
    public const KEY_NOTIFY_RESULT_EMAIL = 'notify_result_email';

    /** Admin: lock examiners from creating new class groups (1 = locked). */
    public const KEY_LOCK_EXAMINER_CREATE_GROUP = 'lock_examiner_create_group';

    /** Admin: allow examiners to create courses (1 = allowed). */
    public const KEY_ALLOW_EXAMINER_CREATE_COURSE = 'allow_examiner_create_course';

    /** Docu Mentor: allow coordinators to delete projects (and groups that have a project). 1 = allowed, 0 = only Super Admin can delete. */
    public const KEY_ALLOW_COORDINATOR_DELETE_PROJECT = 'allow_coordinator_delete_project';

    /** Send SMS to examiner/coordinator on account creation (username, password, login URL). 1 = enabled. Requires phone and Arkesel API key. */
    public const KEY_SEND_SMS_ON_STAFF_CREATION = 'send_sms_on_staff_creation';

    /** Admin: disable strict per-IP/per-device quiz session restrictions (1 = disabled). */
    public const KEY_DISABLE_IP_DEVICE_RESTRICTIONS = 'disable_ip_device_restrictions';

    /** Site in update/maintenance mode: only staff can log in and use the system; others see maintenance page. */
    public const KEY_UPDATE_MODE = 'update_mode';
    /** When update mode was turned on (ISO 8601 datetime). */
    public const KEY_UPDATE_STARTED_AT = 'update_started_at';
    /** Optional estimated end of maintenance (ISO 8601 datetime). */
    public const KEY_UPDATE_ESTIMATED_END = 'update_estimated_end';

    /** OTP (Arkesel): API key and optional sender ID for SMS OTP. */
    public const KEY_OTP_ARKESEL_API_KEY = 'otp_arkesel_api_key';
    public const KEY_OTP_ARKESEL_SENDER_ID = 'otp_arkesel_sender_id';

    /** Super Admin: live examiner view (watch students taking quiz). 1 = on, 0 = off. When off, Live proctor tab and route are unavailable. */
    public const KEY_LIVE_PROCTOR_ENABLED = 'live_proctor_enabled';

    /** AI quiz generation: hours an examiner must wait after exhausting tokens before refill. Default 24. */
    public const KEY_AI_QUIZ_COOLDOWN_HOURS = 'ai_quiz_cooldown_hours';

    /** Digest recipient (primary super admin only). Stored encrypted. */
    public const KEY_NOTIFY_DIGEST_RECIPIENT = 'notify_digest_recipient';

    /** Quiz proctoring (Super Admin): enable/disable features. 1 = enabled, 0 = disabled. */
    public const KEY_PROCTORING_CAMERA_REQUIRED = 'proctoring_camera_required';
    public const KEY_PROCTORING_FACE_MONITOR = 'proctoring_face_monitor';
    public const KEY_PROCTORING_TAB_SWITCH = 'proctoring_tab_switch';
    public const KEY_PROCTORING_OBJECT_DETECT = 'proctoring_object_detect';
    public const KEY_PROCTORING_BLOCK_RIGHT_CLICK = 'proctoring_block_right_click';
    public const KEY_PROCTORING_BLOCK_COPY_PASTE = 'proctoring_block_copy_paste';

    /** Keys whose values are stored encrypted (API keys, secrets, mail password). */
    private const ENCRYPTED_KEYS = [
        self::KEY_GEMINI_API,
        self::KEY_DEEPSEEK_API,
        self::KEY_OPENAI_API,
        self::KEY_CLOUDINARY_API_KEY,
        self::KEY_CLOUDINARY_API_SECRET,
        self::KEY_MAIL_PASSWORD,
        self::KEY_OTP_ARKESEL_API_KEY,
        self::KEY_NOTIFY_DIGEST_RECIPIENT,
    ];

}
