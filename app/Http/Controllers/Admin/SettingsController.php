<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Setting;
use App\Models\User;
use App\Services\StudentUniversalOtp;
use App\Services\AiQuestionService;
use App\Services\ArkeselService;
use App\Services\CloudinaryService;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show settings page (general, email, AI).
     */
    public function index(): View
    {
        $openaiKey = Setting::getValue(Setting::KEY_OPENAI_API);
        $geminiKey = Setting::getValue(Setting::KEY_GEMINI_API);
        $deepseekKey = Setting::getValue(Setting::KEY_DEEPSEEK_API);
        $openaiKeyMasked = $openaiKey ? substr($openaiKey, 0, 8) . '…' . substr($openaiKey, -4) : null;
        $geminiKeyMasked = $geminiKey ? substr($geminiKey, 0, 8) . '…' . substr($geminiKey, -4) : null;
        $deepseekKeyMasked = $deepseekKey ? substr($deepseekKey, 0, 8) . '…' . substr($deepseekKey, -4) : null;

        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $canManageProctoring = $currentUser && $currentUser->isSuperAdmin();
        // Show Backup tab when user is super admin (from model or session – settings route is admin-only)
        $isSuperAdmin = ($currentUser && $currentUser->isSuperAdmin()) || session('admin_role') === User::ROLE_SUPER_ADMIN;
        // Primary can configure backup: match by loaded user id or by session (in case $currentUser is null on some setups)
        $isPrimarySuperAdmin = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );
        $canManageBackup = $isSuperAdmin && $isPrimarySuperAdmin;
        $digestRecipient = $canManageBackup ? Setting::getDigestRecipientValue() : null;
        $backupEmailConfigured = $digestRecipient !== null && trim($digestRecipient) !== '';

        return view('admin.settings.index', [
            'openai_key_set' => (bool) $openaiKey,
            'openai_key_masked' => $openaiKeyMasked,
            'gemini_key_set' => (bool) $geminiKey,
            'gemini_key_masked' => $geminiKeyMasked,
            'deepseek_key_set' => (bool) $deepseekKey,
            'deepseek_key_masked' => $deepseekKeyMasked,
            'app_name' => Setting::getValue(Setting::KEY_APP_NAME, config('app.name')),
            'app_timezone' => Setting::getValue(Setting::KEY_APP_TIMEZONE, config('app.timezone', 'UTC')),
            'footer_copyright' => Setting::getValue(Setting::KEY_FOOTER_COPYRIGHT, '© {year} ' . config('app.name', 'QuizSnap') . '. All rights reserved.'),
            'mail_mailer' => Setting::getValue(Setting::KEY_MAIL_MAILER, 'smtp'),
            'mail_host' => Setting::getValue(Setting::KEY_MAIL_HOST, 'mail.quizsnap.online'),
            'mail_port' => Setting::getValue(Setting::KEY_MAIL_PORT, '465'),
            'mail_username' => Setting::getValue(Setting::KEY_MAIL_USERNAME, 'deveopers@quizsnap.online'),
            'mail_encryption' => Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, 'ssl'),
            'mail_from_address' => Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, 'deveopers@quizsnap.online'),
            'mail_from_name' => Setting::getValue(Setting::KEY_MAIL_FROM_NAME, 'QuizSnap'),
            'notify_result_ready' => Setting::getValue(Setting::KEY_NOTIFY_RESULT_READY, '0') === '1',
            'notify_result_email' => Setting::getValue(Setting::KEY_NOTIFY_RESULT_EMAIL, ''),
            'cloudinary_cloud_name' => Setting::getValue(Setting::KEY_CLOUDINARY_CLOUD_NAME, ''),
            'cloudinary_key_set' => (bool) Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY),
            'cloudinary_key_masked' => ($k = Setting::getValue(Setting::KEY_CLOUDINARY_API_KEY)) ? (strlen($k) > 8 ? substr($k, 0, 4) . '…' . substr($k, -4) : '••••') : null,
            'cloudinary_secret_set' => (bool) Setting::getValue(Setting::KEY_CLOUDINARY_API_SECRET),
            'cloudinary_folder' => Setting::getValue(Setting::KEY_CLOUDINARY_FOLDER, 'quizsnap'),
            'allow_coordinator_delete_project' => Setting::getValue(Setting::KEY_ALLOW_COORDINATOR_DELETE_PROJECT, '1') === '1',
            'send_sms_on_staff_creation' => Setting::getValue(Setting::KEY_SEND_SMS_ON_STAFF_CREATION, '0') === '1',
            'disable_ip_device_restrictions' => Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') === '1',
            'otp_arkesel_key_set' => (bool) Setting::getValue(Setting::KEY_OTP_ARKESEL_API_KEY),
            'otp_arkesel_key_masked' => ($k = Setting::getValue(Setting::KEY_OTP_ARKESEL_API_KEY)) ? (strlen($k) > 8 ? substr($k, 0, 4) . '…' . substr($k, -4) : '••••') : null,
            'otp_arkesel_sender_id' => Setting::getValue(Setting::KEY_OTP_ARKESEL_SENDER_ID, 'QuizSnap'),
            'student_password_login_enabled' => Setting::getValue(Setting::KEY_STUDENT_PASSWORD_LOGIN_ENABLED, '0') === '1',
            'student_universal_otp_codes' => Setting::getValue(Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES, ''),
            'proctoring_camera_required' => Setting::getValue(Setting::KEY_PROCTORING_CAMERA_REQUIRED, '1') === '1',
            'proctoring_face_monitor' => Setting::getValue(Setting::KEY_PROCTORING_FACE_MONITOR, '1') === '1',
            'proctoring_tab_switch' => Setting::getValue(Setting::KEY_PROCTORING_TAB_SWITCH, '1') === '1',
            'proctoring_object_detect' => Setting::getValue(Setting::KEY_PROCTORING_OBJECT_DETECT, '1') === '1',
            'proctoring_block_right_click' => Setting::getValue(Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK, '1') === '1',
            'proctoring_block_copy_paste' => Setting::getValue(Setting::KEY_PROCTORING_BLOCK_COPY_PASTE, '1') === '1',
            'live_proctor_enabled' => Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') === '1',
            'violation_storage_driver' => Setting::getValue(Setting::KEY_VIOLATION_STORAGE_DRIVER, 'server'),
            'violation_retention_days_primary' => Setting::getValue(Setting::KEY_VIOLATION_RETENTION_DAYS_PRIMARY, '21'),
            'violation_retention_days_secondary' => Setting::getValue(Setting::KEY_VIOLATION_RETENTION_DAYS_SECONDARY, '21'),
            'ai_quiz_cooldown_hours' => Setting::getValue(Setting::KEY_AI_QUIZ_COOLDOWN_HOURS, '24'),
            'landing_hero_image' => Setting::getValue(Setting::KEY_LANDING_HERO_IMAGE),
            'landing_hero_enabled' => Setting::getValue(Setting::KEY_LANDING_HERO_ENABLED, '1') === '1',
            'landing_show_quiz_token' => Setting::getValue(Setting::KEY_LANDING_SHOW_QUIZ_TOKEN, '0') === '1',
            'login_hero_image' => Setting::getValue(Setting::KEY_LOGIN_HERO_IMAGE),

            // Supabase Storage (student documents)
            'supabase_url' => Setting::getValue(Setting::KEY_SUPABASE_URL, ''),
            'supabase_bucket' => Setting::getValue(Setting::KEY_SUPABASE_BUCKET, ''),
            'supabase_ttl' => Setting::getValue(Setting::KEY_SUPABASE_SIGNED_URL_TTL, '60'),
            'supabase_service_key_set' => (bool) Setting::getValue(Setting::KEY_SUPABASE_SERVICE_KEY),
            'supabase_service_key_masked' => ($k = Setting::getValue(Setting::KEY_SUPABASE_SERVICE_KEY))
                ? (strlen($k) > 8 ? substr($k, 0, 4) . '…' . substr($k, -4) : '••••')
                : null,

            'can_manage_proctoring' => $canManageProctoring,
            'can_manage_backup' => $canManageBackup,
            'show_backup_tab' => $canManageBackup, // only primary sees the Backup tab
            'backup_email_configured' => $backupEmailConfigured,
            'study_guide_unlocked' => session('study_guide_unlocked', false),
            'class_groups_for_study_guide' => ($canManageBackup && session('study_guide_unlocked', false))
                ? ClassGroup::orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    /**
     * Validate study guide password and unlock access (Settings → Digest). Primary super admin only.
     */
    public function studyGuideUnlock(Request $request): RedirectResponse
    {
        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $isPrimary = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );

        if (! $isPrimary) {
            return redirect()->route('dashboard.settings.index')->with('error', 'Access denied.')->withFragment('backup');
        }

        $request->validate(['study_guide_password' => 'required|string']);

        $expected = trim((string) (config('study-guide.unlock_password') ?? env('STUDY_GUIDE_UNLOCK_PASSWORD', 'Atomic2@2020^') ?? ''));
        $given = trim((string) $request->input('study_guide_password', ''));
        if ($expected === '' || ! hash_equals($expected, $given)) {
            return redirect()->route('dashboard.settings.index')->with('error', 'Invalid password.')->withFragment('backup');
        }

        session(['study_guide_unlocked' => true]);
        session()->forget('error');

        return redirect()->route('dashboard.settings.index')->with('success', 'Unlocked.')->withFragment('backup');
    }

    /**
     * Update settings (general, email, AI).
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_timezone' => 'nullable|string|max:100',
            'footer_copyright' => 'nullable|string|max:512',
            'mail_mailer' => 'nullable|string|max:50',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|string|max:10',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:512',
            'mail_encryption' => 'nullable|string|max:20',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'notify_result_ready' => 'nullable|boolean',
            'notify_result_email' => 'nullable|email|max:255',
            'openai_api_key' => 'nullable|string|max:512',
            'clear_openai_key' => 'nullable|boolean',
            'gemini_api_key' => 'nullable|string|max:512',
            'clear_gemini_key' => 'nullable|boolean',
            'deepseek_api_key' => 'nullable|string|max:512',
            'clear_deepseek_key' => 'nullable|boolean',
            'cloudinary_cloud_name' => 'nullable|string|max:128',
            'cloudinary_api_key' => 'nullable|string|max:128',
            'cloudinary_api_secret' => 'nullable|string|max:512',
            'cloudinary_folder' => 'nullable|string|max:128',
            'send_sms_on_staff_creation' => 'nullable|boolean',
            'disable_ip_device_restrictions' => 'nullable|boolean',
            'otp_arkesel_api_key' => 'nullable|string|max:512',
            'clear_otp_arkesel_key' => 'nullable|boolean',
            'otp_arkesel_sender_id' => 'nullable|string|max:11',
            'student_password_login_enabled' => 'nullable|boolean',
            'student_universal_otp_codes' => 'nullable|string|max:500',
            'proctoring_camera_required' => 'nullable|boolean',
            'proctoring_face_monitor' => 'nullable|boolean',
            'proctoring_tab_switch' => 'nullable|boolean',
            'proctoring_object_detect' => 'nullable|boolean',
            'proctoring_block_right_click' => 'nullable|boolean',
            'proctoring_block_copy_paste' => 'nullable|boolean',
            'live_proctor_enabled' => 'nullable|boolean',
            'violation_storage_driver' => 'nullable|string|in:cloudinary,server',
            'violation_retention_days_primary' => 'nullable|integer|min:1|max:365',
            'violation_retention_days_secondary' => 'nullable|integer|min:1|max:365',
            'ai_quiz_cooldown_hours' => 'nullable|integer|min:1|max:168',
            'landing_hero_image_url' => 'nullable|string|max:2048',
            'landing_hero_image_file' => 'nullable|image|max:5120',
            'login_hero_image_file' => 'nullable|image|max:5120',
            'login_hero_image_url' => 'nullable|url|max:2048',
            'landing_hero_enabled' => 'nullable|boolean',
            'landing_show_quiz_token' => 'nullable|boolean',
            'notify_digest_recipient' => 'nullable|email|max:255',

            // Supabase Storage
            'supabase_url' => 'nullable|url|max:255',
            'supabase_service_key' => 'nullable|string|max:1024',
            'clear_supabase_service_key' => 'nullable|boolean',
            'supabase_bucket' => 'nullable|string|max:255',
            'supabase_signed_url_ttl' => 'nullable|integer|min:1|max:1440',
        ]);

        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $canManageProctoring = $currentUser && $currentUser->isSuperAdmin();
        $isPrimarySuperAdmin = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );
        $canManageBackup = $isPrimarySuperAdmin;

        // Only update digest recipient when a new value is actually entered; do not overwrite with empty when user saves from another tab (Digest field shows placeholder and submits empty).
        if ($canManageBackup && $request->filled('notify_digest_recipient')) {
            Setting::setDigestRecipientValue(trim($request->notify_digest_recipient));
            Cache::forget('setting:' . Setting::KEY_NOTIFY_DIGEST_RECIPIENT_STORAGE);
        }

        Setting::setValue(Setting::KEY_APP_NAME, $request->filled('app_name') ? trim($request->app_name) : null);
        Setting::setValue(Setting::KEY_APP_TIMEZONE, $request->filled('app_timezone') ? trim($request->app_timezone) : null);
        Setting::setValue(Setting::KEY_FOOTER_COPYRIGHT, $request->filled('footer_copyright') ? trim($request->footer_copyright) : null);

        Setting::setValue(Setting::KEY_MAIL_MAILER, $request->filled('mail_mailer') ? trim($request->mail_mailer) : null);
        Setting::setValue(Setting::KEY_MAIL_HOST, $request->filled('mail_host') ? trim($request->mail_host) : null);
        Setting::setValue(Setting::KEY_MAIL_PORT, $request->filled('mail_port') ? trim($request->mail_port) : null);
        Setting::setValue(Setting::KEY_MAIL_USERNAME, $request->filled('mail_username') ? trim($request->mail_username) : null);
        if ($request->filled('mail_password')) {
            Setting::setValue(Setting::KEY_MAIL_PASSWORD, trim($request->mail_password));
        }
        Setting::setValue(Setting::KEY_MAIL_ENCRYPTION, $request->filled('mail_encryption') ? trim($request->mail_encryption) : null);
        Setting::setValue(Setting::KEY_MAIL_FROM_ADDRESS, $request->filled('mail_from_address') ? trim($request->mail_from_address) : null);
        Setting::setValue(Setting::KEY_MAIL_FROM_NAME, $request->filled('mail_from_name') ? trim($request->mail_from_name) : null);
        Setting::setValue(Setting::KEY_NOTIFY_RESULT_READY, $request->boolean('notify_result_ready') ? '1' : '0');
        Setting::setValue(Setting::KEY_NOTIFY_RESULT_EMAIL, $request->filled('notify_result_email') ? trim($request->notify_result_email) : null);

        if ($request->boolean('clear_openai_key')) {
            Setting::setValue(Setting::KEY_OPENAI_API, null);
        } elseif ($request->filled('openai_api_key')) {
            Setting::setValue(Setting::KEY_OPENAI_API, trim($request->openai_api_key));
        }
        if ($request->boolean('clear_gemini_key')) {
            Setting::setValue(Setting::KEY_GEMINI_API, null);
        } elseif ($request->filled('gemini_api_key')) {
            Setting::setValue(Setting::KEY_GEMINI_API, trim($request->gemini_api_key));
        }
        if ($request->boolean('clear_deepseek_key')) {
            Setting::setValue(Setting::KEY_DEEPSEEK_API, null);
        } elseif ($request->filled('deepseek_api_key')) {
            Setting::setValue(Setting::KEY_DEEPSEEK_API, trim($request->deepseek_api_key));
        }

        // Ensure AI key caches are cleared so admin dashboard and question generation use fresh DB values
        if ($request->hasAny(['openai_api_key', 'clear_openai_key', 'gemini_api_key', 'clear_gemini_key', 'deepseek_api_key', 'clear_deepseek_key'])) {
            Cache::forget('setting:' . Setting::KEY_OPENAI_API);
            Cache::forget('setting:' . Setting::KEY_GEMINI_API);
            Cache::forget('setting:' . Setting::KEY_DEEPSEEK_API);

            \Illuminate\Support\Facades\Log::info('AI settings updated', [
                'openai_updated' => $request->filled('openai_api_key'),
                'openai_cleared' => $request->boolean('clear_openai_key'),
                'gemini_updated' => $request->filled('gemini_api_key'),
                'gemini_cleared' => $request->boolean('clear_gemini_key'),
                'deepseek_updated' => $request->filled('deepseek_api_key'),
                'deepseek_cleared' => $request->boolean('clear_deepseek_key'),
            ]);
        }

        Setting::setValue(Setting::KEY_CLOUDINARY_CLOUD_NAME, $request->filled('cloudinary_cloud_name') ? trim($request->cloudinary_cloud_name) : null);
        if ($request->filled('cloudinary_api_key')) {
            Setting::setValue(Setting::KEY_CLOUDINARY_API_KEY, trim($request->cloudinary_api_key));
        }
        // Save API secret when provided (password field is always sent empty by browser when blank)
        $apiSecret = $request->input('cloudinary_api_secret');
        if (is_string($apiSecret) && trim($apiSecret) !== '') {
            Setting::setValue(Setting::KEY_CLOUDINARY_API_SECRET, trim($apiSecret));
        }
        Setting::setValue(Setting::KEY_CLOUDINARY_FOLDER, $request->filled('cloudinary_folder') ? trim($request->cloudinary_folder) : 'quizsnap');
        if (session('admin_role') === 'super_admin') {
            Setting::setValue(Setting::KEY_ALLOW_COORDINATOR_DELETE_PROJECT, $request->boolean('allow_coordinator_delete_project') ? '1' : '0');
            Setting::setValue(Setting::KEY_SEND_SMS_ON_STAFF_CREATION, $request->boolean('send_sms_on_staff_creation') ? '1' : '0');
            Cache::forget('setting:' . Setting::KEY_ALLOW_COORDINATOR_DELETE_PROJECT);
            Cache::forget('setting:' . Setting::KEY_SEND_SMS_ON_STAFF_CREATION);
        }
        Setting::setValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, $request->boolean('disable_ip_device_restrictions') ? '1' : '0');

        if ($request->boolean('clear_otp_arkesel_key')) {
            Setting::setValue(Setting::KEY_OTP_ARKESEL_API_KEY, null);
        } elseif ($request->filled('otp_arkesel_api_key')) {
            Setting::setValue(Setting::KEY_OTP_ARKESEL_API_KEY, trim($request->otp_arkesel_api_key));
        }
        Setting::setValue(Setting::KEY_OTP_ARKESEL_SENDER_ID, $request->filled('otp_arkesel_sender_id') ? substr(trim($request->otp_arkesel_sender_id), 0, 11) : 'QuizSnap');
        if ($request->input('settings_tab') === 'otp') {
            Setting::setValue(Setting::KEY_STUDENT_PASSWORD_LOGIN_ENABLED, $request->boolean('student_password_login_enabled') ? '1' : '0');
            Cache::forget('setting:' . Setting::KEY_STUDENT_PASSWORD_LOGIN_ENABLED);

            $uniRaw = (string) $request->input('student_universal_otp_codes', '');
            $uniNorm = implode(',', StudentUniversalOtp::parseRawToSixDigitCodes($uniRaw));
            if ($uniNorm === '') {
                Setting::where('key', Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES)->delete();
            } else {
                Setting::setValue(Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES, $uniNorm);
            }
            Cache::forget('setting:' . Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES);
        }
        if ($request->hasAny(['otp_arkesel_api_key', 'clear_otp_arkesel_key', 'otp_arkesel_sender_id'])) {
            Cache::forget('setting:' . Setting::KEY_OTP_ARKESEL_API_KEY);
            Cache::forget('setting:' . Setting::KEY_OTP_ARKESEL_SENDER_ID);
        }
        if ($canManageProctoring) {
            Setting::setValue(Setting::KEY_LIVE_PROCTOR_ENABLED, $request->boolean('live_proctor_enabled') ? '1' : '0');
            Setting::setValue(Setting::KEY_PROCTORING_CAMERA_REQUIRED, $request->boolean('proctoring_camera_required') ? '1' : '0');
            Setting::setValue(Setting::KEY_PROCTORING_FACE_MONITOR, $request->boolean('proctoring_face_monitor') ? '1' : '0');
            Setting::setValue(Setting::KEY_PROCTORING_TAB_SWITCH, $request->boolean('proctoring_tab_switch') ? '1' : '0');
            Setting::setValue(Setting::KEY_PROCTORING_OBJECT_DETECT, $request->boolean('proctoring_object_detect') ? '1' : '0');
            Setting::setValue(Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK, $request->boolean('proctoring_block_right_click') ? '1' : '0');
            Setting::setValue(Setting::KEY_PROCTORING_BLOCK_COPY_PASTE, $request->boolean('proctoring_block_copy_paste') ? '1' : '0');
            if (in_array($request->input('violation_storage_driver'), ['cloudinary', 'server'], true)) {
                Setting::setValue(Setting::KEY_VIOLATION_STORAGE_DRIVER, $request->input('violation_storage_driver'));
            }
            $primaryDays = $request->input('violation_retention_days_primary');
            if ($primaryDays !== null && $primaryDays !== '') {
                $primaryDays = max(1, min(365, (int) $primaryDays));
                Setting::setValue(Setting::KEY_VIOLATION_RETENTION_DAYS_PRIMARY, (string) $primaryDays);
            }
            $secondaryDays = $request->input('violation_retention_days_secondary');
            if ($secondaryDays !== null && $secondaryDays !== '') {
                $secondaryDays = max(1, min(365, (int) $secondaryDays));
                Setting::setValue(Setting::KEY_VIOLATION_RETENTION_DAYS_SECONDARY, (string) $secondaryDays);
            }
        }
        if ($request->has('ai_quiz_cooldown_hours')) {
            $hours = max(1, min(168, (int) $request->ai_quiz_cooldown_hours));
            Setting::setValue(Setting::KEY_AI_QUIZ_COOLDOWN_HOURS, (string) $hours);
            Cache::forget('setting:' . Setting::KEY_AI_QUIZ_COOLDOWN_HOURS);
        }

        // Supabase Storage: URL, bucket, service key (encrypted), signed URL TTL
        if ($request->filled('supabase_url')) {
            Setting::setValue(Setting::KEY_SUPABASE_URL, trim($request->supabase_url));
        }
        if ($request->boolean('clear_supabase_service_key')) {
            Setting::setValue(Setting::KEY_SUPABASE_SERVICE_KEY, null);
        } elseif ($request->filled('supabase_service_key')) {
            Setting::setValue(Setting::KEY_SUPABASE_SERVICE_KEY, trim($request->supabase_service_key));
        }
        if ($request->filled('supabase_bucket')) {
            Setting::setValue(Setting::KEY_SUPABASE_BUCKET, trim($request->supabase_bucket));
        }
        if ($request->has('supabase_signed_url_ttl')) {
            $ttl = max(1, min(1440, (int) $request->supabase_signed_url_ttl));
            Setting::setValue(Setting::KEY_SUPABASE_SIGNED_URL_TTL, (string) $ttl);
        }
        if (session('admin_role') === 'super_admin') {
            Setting::setValue(Setting::KEY_LANDING_HERO_ENABLED, $request->boolean('landing_hero_enabled') ? '1' : '0');
            Cache::forget('setting:' . Setting::KEY_LANDING_HERO_ENABLED);
            if ($request->user() && $request->user()->role === \App\Models\User::ROLE_SUPER_ADMIN) {
                Setting::setValue(Setting::KEY_LANDING_SHOW_QUIZ_TOKEN, $request->boolean('landing_show_quiz_token') ? '1' : '0');
                Cache::forget('setting:' . Setting::KEY_LANDING_SHOW_QUIZ_TOKEN);
            }
            if ($request->hasFile('landing_hero_image_file')) {
                $url = CloudinaryService::uploadFromFile($request->file('landing_hero_image_file'));
                if ($url) {
                    Setting::setValue(Setting::KEY_LANDING_HERO_IMAGE, $url);
                    Cache::forget('setting:' . Setting::KEY_LANDING_HERO_IMAGE);
                }
            } elseif ($request->filled('landing_hero_image_url')) {
                $url = trim(preg_replace('/[\r\n]+/', '', $request->landing_hero_image_url));
                if ($url !== '' && (preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL))) {
                    Setting::setValue(Setting::KEY_LANDING_HERO_IMAGE, $url);
                    Cache::forget('setting:' . Setting::KEY_LANDING_HERO_IMAGE);
                }
            }
            // Login page hero image: direct URL or upload (stored on Cloudinary)
            if ($request->hasFile('login_hero_image_file')) {
                $url = CloudinaryService::uploadFromFile($request->file('login_hero_image_file'));
                if ($url) {
                    Setting::setValue(Setting::KEY_LOGIN_HERO_IMAGE, $url);
                    Cache::forget('setting:' . Setting::KEY_LOGIN_HERO_IMAGE);
                }
            } elseif ($request->filled('login_hero_image_url')) {
                $url = trim(preg_replace('/[\r\n]+/', '', $request->login_hero_image_url));
                if ($url !== '' && (preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL))) {
                    Setting::setValue(Setting::KEY_LOGIN_HERO_IMAGE, $url);
                    Cache::forget('setting:' . Setting::KEY_LOGIN_HERO_IMAGE);
                }
            }
        }
        if ($canManageProctoring) {
            foreach ([
                Setting::KEY_LIVE_PROCTOR_ENABLED,
                Setting::KEY_PROCTORING_CAMERA_REQUIRED,
                Setting::KEY_PROCTORING_FACE_MONITOR,
                Setting::KEY_PROCTORING_TAB_SWITCH,
                Setting::KEY_PROCTORING_OBJECT_DETECT,
                Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK,
                Setting::KEY_PROCTORING_BLOCK_COPY_PASTE,
                Setting::KEY_VIOLATION_STORAGE_DRIVER,
                Setting::KEY_VIOLATION_RETENTION_DAYS_PRIMARY,
                Setting::KEY_VIOLATION_RETENTION_DAYS_SECONDARY,
            ] as $key) {
                Cache::forget('setting:' . $key);
            }
        }

        // Ensure cache is cleared so Test Cloudinary / uploads use fresh DB values
        if ($request->hasAny(['cloudinary_cloud_name', 'cloudinary_api_key', 'cloudinary_api_secret', 'cloudinary_folder'])) {
            Cache::forget('setting:' . Setting::KEY_CLOUDINARY_CLOUD_NAME);
            Cache::forget('setting:' . Setting::KEY_CLOUDINARY_API_KEY);
            Cache::forget('setting:' . Setting::KEY_CLOUDINARY_API_SECRET);
            Cache::forget('setting:' . Setting::KEY_CLOUDINARY_FOLDER);
        }

        // Supabase Storage settings cache
        if ($request->hasAny(['supabase_url', 'supabase_service_key', 'clear_supabase_service_key', 'supabase_bucket', 'supabase_signed_url_ttl'])) {
            Cache::forget('setting:' . Setting::KEY_SUPABASE_URL);
            Cache::forget('setting:' . Setting::KEY_SUPABASE_SERVICE_KEY);
            Cache::forget('setting:' . Setting::KEY_SUPABASE_BUCKET);
            Cache::forget('setting:' . Setting::KEY_SUPABASE_SIGNED_URL_TTL);
        }

        $tab = $request->input('settings_tab', 'general');
        $validTabs = ['general', 'email', 'ai', 'cloudinary', 'supabase', 'otp', 'proctoring', 'backup'];
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'general';
        }
        return redirect()->route('dashboard.settings.index')->with('success', 'Settings saved.')->withFragment($tab);
    }

    /**
     * Test AI connection (Gemini / DeepSeek). Returns JSON for API or Settings page.
     */
    public function aiTest(AiQuestionService $ai): JsonResponse
    {
        // Always read the latest saved keys when testing.
        Cache::forget('setting:' . Setting::KEY_OPENAI_API);
        Cache::forget('setting:' . Setting::KEY_GEMINI_API);
        Cache::forget('setting:' . Setting::KEY_DEEPSEEK_API);

        $openaiPresent = Setting::getValue(Setting::KEY_OPENAI_API) !== null;
        $geminiPresent = Setting::getValue(Setting::KEY_GEMINI_API) !== null;
        $deepseekPresent = Setting::getValue(Setting::KEY_DEEPSEEK_API) !== null;

        $result = $ai->testConnection();

        \Illuminate\Support\Facades\Log::info('AI connection test executed', [
            'openai_key_present' => $openaiPresent,
            'gemini_key_present' => $geminiPresent,
            'deepseek_key_present' => $deepseekPresent,
            'success' => (bool) ($result['success'] ?? false),
            'provider' => $result['provider'] ?? null,
            'message' => $result['message'] ?? null,
        ]);

        return response()
            ->json($result, $result['success'] ? 200 : 422)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Test Cloudinary connection. Returns JSON for Settings page.
     */
    public function cloudinaryTest(): JsonResponse
    {
        $result = CloudinaryService::testConnection();
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Test Supabase Storage connection. Returns JSON for Settings page.
     */
    public function supabaseTest(): JsonResponse
    {
        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $isPrimarySuperAdmin = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );

        if (! $isPrimarySuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Only the primary super admin can test Supabase from settings.',
            ], 403);
        }

        $result = SupabaseStorageService::testConnection();
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Send a test email using current mail settings.
     * Restricted to the primary super admin.
     */
    public function emailTest(Request $request): JsonResponse
    {
        $request->validate(['to' => 'required|email|max:255']);

        $currentUser = auth()->user() ?? User::find(session('admin_user_id'));
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $isPrimarySuperAdmin = $primarySuperAdminId !== null && (
            ($currentUser && (int) $currentUser->id === (int) $primarySuperAdminId)
            || ((int) session('admin_user_id') === (int) $primarySuperAdminId)
        );

        if (! $isPrimarySuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Only the primary super admin can send test email from settings.',
            ], 403);
        }

        try {
            $this->applyMailConfigFromSettings();

            $to = trim((string) $request->input('to'));
            $mailer = (string) Setting::getValue(Setting::KEY_MAIL_MAILER, (string) config('mail.default'));
            $host = (string) Setting::getValue(Setting::KEY_MAIL_HOST, (string) config('mail.mailers.smtp.host'));
            $port = (string) Setting::getValue(Setting::KEY_MAIL_PORT, (string) (config('mail.mailers.smtp.port') ?? ''));
            $encryption = (string) Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, (string) (config('mail.mailers.smtp.encryption') ?? ''));
            $fromAddress = (string) Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, (string) config('mail.from.address'));
            $fromName = (string) Setting::getValue(Setting::KEY_MAIL_FROM_NAME, (string) config('mail.from.name'));

            $body = "QuizSnap test email\n\n"
                . 'Time: ' . now()->toDateTimeString() . "\n"
                . 'Mailer: ' . ($mailer ?: '—') . "\n"
                . 'Host: ' . ($host ?: '—') . "\n"
                . 'Port: ' . ($port ?: '—') . "\n"
                . 'Encryption: ' . ($encryption !== '' ? $encryption : 'none') . "\n"
                . 'From: ' . ($fromName ?: 'QuizSnap') . ' <' . ($fromAddress ?: 'noreply@quizsnap.local') . ">\n\n"
                . "If you received this, your mail settings are working.";

            Mail::raw($body, function ($message) use ($to) {
                $message->to($to)->subject('QuizSnap mail test');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent to ' . $to . '.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email.',
                'detail' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test OTP delivery (Arkesel). Sends a test SMS with a 6-digit code to the given phone number.
     */
    public function otpTest(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|max:20']);
        $phone = preg_replace('/\D/', '', $request->input('phone'));
        if (strlen($phone) < 10) {
            return response()->json(['success' => false, 'message' => 'Enter a valid phone number (e.g. 233544919953 or 0544919953).'], 422);
        }
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '233' . substr($phone, 1);
        } elseif (strlen($phone) === 9 && in_array(substr($phone, 0, 1), ['4', '5', '6'], true)) {
            $phone = '233' . $phone;
        }
        $result = ArkeselService::sendTestOtp($phone);
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Check Arkesel SMS/main balance. Helps debug "not receiving" (e.g. zero balance).
     */
    public function otpBalance(): JsonResponse
    {
        $result = ArkeselService::checkBalance();
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function applyMailConfigFromSettings(): void
    {
        $mailer = Setting::getValue(Setting::KEY_MAIL_MAILER, config('mail.default'));
        $host = Setting::getValue(Setting::KEY_MAIL_HOST, config('mail.mailers.smtp.host'));
        $port = (int) Setting::getValue(Setting::KEY_MAIL_PORT, (string) (config('mail.mailers.smtp.port') ?? 587));
        $username = Setting::getValue(Setting::KEY_MAIL_USERNAME);
        $password = Setting::getValue(Setting::KEY_MAIL_PASSWORD);
        $encryption = Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, (string) (config('mail.mailers.smtp.encryption') ?? 'tls'));
        $fromAddress = Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, config('mail.from.address'));
        $fromName = Setting::getValue(Setting::KEY_MAIL_FROM_NAME, config('mail.from.name'));

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress ?: 'noreply@quizsnap.local');
        Config::set('mail.from.name', $fromName ?: 'QuizSnap');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
    }

    /**
     * Toggle update/maintenance mode. When on, only staff can log in; others see maintenance page.
     */
    public function toggleUpdateMode(Request $request): RedirectResponse
    {
        $current = Setting::getValue(Setting::KEY_UPDATE_MODE, '0') === '1';
        Setting::setValue(Setting::KEY_UPDATE_MODE, $current ? '0' : '1');
        if ($current) {
            Setting::setValue(Setting::KEY_UPDATE_STARTED_AT, null);
            Setting::setValue(Setting::KEY_UPDATE_ESTIMATED_END, null);
        } else {
            Setting::setValue(Setting::KEY_UPDATE_STARTED_AT, now()->toIso8601String());
        }
        Cache::forget('setting:' . Setting::KEY_UPDATE_MODE);
        Cache::forget('setting:' . Setting::KEY_UPDATE_STARTED_AT);
        Cache::forget('setting:' . Setting::KEY_UPDATE_ESTIMATED_END);
        return redirect()->route('dashboard')->with('success', $current ? 'Update mode turned off. Site is live.' : 'Update mode is on. Only staff can sign in.');
    }

    /**
     * Set optional estimated end time for maintenance (shown on maintenance page).
     */
    public function setUpdateEstimatedEnd(Request $request): RedirectResponse
    {
        $request->validate(['estimated_end' => ['nullable', 'date']]);
        $value = $request->input('estimated_end') ? \Carbon\Carbon::parse($request->input('estimated_end'))->toIso8601String() : null;
        Setting::setValue(Setting::KEY_UPDATE_ESTIMATED_END, $value);
        Cache::forget('setting:' . Setting::KEY_UPDATE_ESTIMATED_END);
        return redirect()->route('dashboard')->with('success', $value ? 'Estimated end time saved.' : 'Estimated end time cleared.');
    }
}
