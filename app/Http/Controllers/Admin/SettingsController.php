<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiQuestionService;
use App\Services\ArkeselService;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
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
            'lock_examiner_create_group' => Setting::getValue(Setting::KEY_LOCK_EXAMINER_CREATE_GROUP, '0') === '1',
            'allow_examiner_create_course' => Setting::getValue(Setting::KEY_ALLOW_EXAMINER_CREATE_COURSE, '0') === '1',
            'allow_coordinator_delete_project' => Setting::getValue(Setting::KEY_ALLOW_COORDINATOR_DELETE_PROJECT, '1') === '1',
            'send_sms_on_staff_creation' => Setting::getValue(Setting::KEY_SEND_SMS_ON_STAFF_CREATION, '0') === '1',
            'disable_ip_device_restrictions' => Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') === '1',
            'otp_arkesel_key_set' => (bool) Setting::getValue(Setting::KEY_OTP_ARKESEL_API_KEY),
            'otp_arkesel_key_masked' => ($k = Setting::getValue(Setting::KEY_OTP_ARKESEL_API_KEY)) ? (strlen($k) > 8 ? substr($k, 0, 4) . '…' . substr($k, -4) : '••••') : null,
            'otp_arkesel_sender_id' => Setting::getValue(Setting::KEY_OTP_ARKESEL_SENDER_ID, 'QuizSnap'),
            'proctoring_camera_required' => Setting::getValue(Setting::KEY_PROCTORING_CAMERA_REQUIRED, '1') === '1',
            'proctoring_face_monitor' => Setting::getValue(Setting::KEY_PROCTORING_FACE_MONITOR, '1') === '1',
            'proctoring_tab_switch' => Setting::getValue(Setting::KEY_PROCTORING_TAB_SWITCH, '1') === '1',
            'proctoring_object_detect' => Setting::getValue(Setting::KEY_PROCTORING_OBJECT_DETECT, '1') === '1',
            'proctoring_block_right_click' => Setting::getValue(Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK, '1') === '1',
            'proctoring_block_copy_paste' => Setting::getValue(Setting::KEY_PROCTORING_BLOCK_COPY_PASTE, '1') === '1',
            'live_proctor_enabled' => Setting::getValue(Setting::KEY_LIVE_PROCTOR_ENABLED, '1') === '1',
            'ai_quiz_cooldown_hours' => Setting::getValue(Setting::KEY_AI_QUIZ_COOLDOWN_HOURS, '24'),
            'landing_hero_image' => Setting::getValue(Setting::KEY_LANDING_HERO_IMAGE),
            'landing_hero_enabled' => Setting::getValue(Setting::KEY_LANDING_HERO_ENABLED, '1') === '1',
            'landing_show_quiz_token' => Setting::getValue(Setting::KEY_LANDING_SHOW_QUIZ_TOKEN, '0') === '1',
            'can_manage_proctoring' => $canManageProctoring,
            'can_manage_backup' => $canManageBackup,
            'show_backup_tab' => $canManageBackup, // only primary sees the Backup tab
            'backup_email_configured' => $backupEmailConfigured,
        ]);
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
            'lock_examiner_create_group' => 'nullable|boolean',
            'allow_examiner_create_course' => 'nullable|boolean',
            'send_sms_on_staff_creation' => 'nullable|boolean',
            'disable_ip_device_restrictions' => 'nullable|boolean',
            'otp_arkesel_api_key' => 'nullable|string|max:512',
            'clear_otp_arkesel_key' => 'nullable|boolean',
            'otp_arkesel_sender_id' => 'nullable|string|max:11',
            'proctoring_camera_required' => 'nullable|boolean',
            'proctoring_face_monitor' => 'nullable|boolean',
            'proctoring_tab_switch' => 'nullable|boolean',
            'proctoring_object_detect' => 'nullable|boolean',
            'proctoring_block_right_click' => 'nullable|boolean',
            'proctoring_block_copy_paste' => 'nullable|boolean',
            'live_proctor_enabled' => 'nullable|boolean',
            'ai_quiz_cooldown_hours' => 'nullable|integer|min:1|max:168',
            'landing_hero_image_url' => 'nullable|string|max:2048',
            'landing_hero_image_file' => 'nullable|image|max:5120',
            'landing_hero_enabled' => 'nullable|boolean',
            'landing_show_quiz_token' => 'nullable|boolean',
            'notify_digest_recipient' => 'nullable|email|max:255',
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
        Setting::setValue(Setting::KEY_LOCK_EXAMINER_CREATE_GROUP, $request->boolean('lock_examiner_create_group') ? '1' : '0');
        Setting::setValue(Setting::KEY_ALLOW_EXAMINER_CREATE_COURSE, $request->boolean('allow_examiner_create_course') ? '1' : '0');
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
        }
        if ($request->has('ai_quiz_cooldown_hours')) {
            $hours = max(1, min(168, (int) $request->ai_quiz_cooldown_hours));
            Setting::setValue(Setting::KEY_AI_QUIZ_COOLDOWN_HOURS, (string) $hours);
            Cache::forget('setting:' . Setting::KEY_AI_QUIZ_COOLDOWN_HOURS);
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

        $tab = $request->input('settings_tab', 'general');
        $validTabs = ['general', 'email', 'ai', 'cloudinary', 'otp', 'proctoring', 'backup'];
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
