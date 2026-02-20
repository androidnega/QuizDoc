@extends('layouts.dashboard')

@section('title', 'Settings')
@section('dashboard_heading', 'Settings')

@section('dashboard_content')
<div class="w-full space-y-6">
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900 font-medium">Settings</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Admin Settings</h1>
            <p class="text-gray-600 mt-1">System configuration: general, email, AI, and Cloudinary</p>
            <a href="{{ route('dashboard.student-levels.index') }}" class="mt-2 inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800">Student Levels →</a>
        </div>

        <form action="{{ route('dashboard.settings.update') }}" method="post" class="space-y-8" id="settings-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="settings_tab" id="settings_tab" value="general">

            <!-- Tabs Navigation -->
            <div class="card overflow-hidden">
                <div class="border-b border-gray-200 overflow-x-auto overflow-y-hidden">
                    <nav class="flex -mb-px flex-nowrap min-w-0 w-max sm:w-full sm:flex-wrap" aria-label="Settings tabs">
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="general" id="tab-btn-general">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            General
                        </button>
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="email" id="tab-btn-email">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Email
                        </button>
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="ai" id="tab-btn-ai">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            AI
                        </button>
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="cloudinary" id="tab-btn-cloudinary">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Cloudinary
                        </button>
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="otp" id="tab-btn-otp">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            OTP (SMS)
                        </button>
                        @if($can_manage_proctoring ?? false)
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="proctoring" id="tab-btn-proctoring">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Proctoring
                        </button>
                        @endif
                        @if($show_backup_tab ?? false)
                        <button type="button" class="settings-tab-btn whitespace-nowrap px-4 py-3 sm:px-6 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm touch-manipulation min-h-[44px]" data-tab="backup" id="tab-btn-backup">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Digest
                        </button>
                        @endif
                    </nav>
                </div>

                <!-- Tab: General -->
                <div class="settings-tab-content p-6" data-tab-content="general" id="tab-content-general">
                    <div class="space-y-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">App & branding</h3>
                            <div>
                                <label for="app_name" class="block text-sm font-medium text-gray-700 mb-1.5">Application name</label>
                                <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $app_name ?? '') }}" placeholder="QuizSnap" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Used in page titles and emails. Leave blank to use default.</p>
                            </div>
                            <div>
                                <label for="app_timezone" class="block text-sm font-medium text-gray-700 mb-1.5">Timezone</label>
                                <input type="text" name="app_timezone" id="app_timezone" value="{{ old('app_timezone', $app_timezone ?? 'UTC') }}" placeholder="e.g. UTC, Africa/Nairobi, America/New_York" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">e.g. UTC, Africa/Nairobi, America/New_York</p>
                            </div>
                            <div>
                                <label for="footer_copyright" class="block text-sm font-medium text-gray-700 mb-1.5">Copyright / footer text</label>
                                <input type="text" name="footer_copyright" id="footer_copyright" value="{{ old('footer_copyright', $footer_copyright ?? '') }}" placeholder="© {year} QuizSnap. All rights reserved." maxlength="512" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Shown at the bottom of Settings. Use <code class="px-1 py-0.5 bg-gray-100 rounded text-gray-700">{year}</code> for the current year (updates automatically).</p>
                            </div>
                        </div>

                        @if(session('admin_role') === 'super_admin')
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Examiner permissions</h3>
                            <div class="space-y-3">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="lock_examiner_create_group" value="1" {{ old('lock_examiner_create_group', $lock_examiner_create_group ?? false) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Lock examiners from creating new class groups</span>
                                </label>
                                <p class="text-xs text-gray-500 ml-7">When on, only Super Admin can create class groups. Examiners can still view and edit existing groups.</p>
                            </div>
                            <div class="space-y-3 pt-2 border-t border-gray-200">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="allow_examiner_create_course" value="1" {{ old('allow_examiner_create_course', $allow_examiner_create_course ?? false) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Allow examiners to create courses</span>
                                </label>
                                <p class="text-xs text-gray-500 ml-7">When enabled, examiners can create and manage courses. By default, only Super Admin can create courses.</p>
                            </div>
                            <div class="space-y-3 pt-2 border-t border-gray-200">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="disable_ip_device_restrictions" value="1" {{ old('disable_ip_device_restrictions', $disable_ip_device_restrictions ?? false) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Allow shared networks/PCs for quiz access</span>
                                </label>
                                <p class="text-xs text-gray-500 ml-7">Students can take the same quiz from the same network or reused computers. Disables IP/device uniqueness and session IP mismatch blocking.</p>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Docu Mentor – Coordinator</h3>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="allow_coordinator_delete_project" value="1" {{ old('allow_coordinator_delete_project', $allow_coordinator_delete_project ?? true) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Allow coordinators to delete projects and groups that have a project</span>
                            </label>
                            <p class="text-xs text-gray-500 ml-7">When on, coordinators can delete any Docu Mentor project (and the group). When off, only Super Admin can delete.</p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Staff account creation – SMS</h3>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="send_sms_on_staff_creation" value="1" {{ old('send_sms_on_staff_creation', $send_sms_on_staff_creation ?? false) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Send SMS to examiner/coordinator on account creation</span>
                            </label>
                            <p class="text-xs text-gray-500 ml-7">New staff receive an SMS with username, password and login URL. Requires phone number and Arkesel API key (OTP tab). When off, admin sets the password manually.</p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Mobile landing hero image</h3>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="landing_hero_enabled" value="1" {{ old('landing_hero_enabled', $landing_hero_enabled ?? true) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Show hero section on mobile homepage</span>
                            </label>
                            <p class="text-xs text-gray-500 ml-7">Hero image is shown on phones only (below the header). Use a URL or upload; uploads are stored on Cloudinary.</p>
                            @if(!empty(trim($landing_hero_image ?? '')))
                                @php $heroImgUrl = trim($landing_hero_image); @endphp
                                <div class="pt-2 border-t border-gray-200">
                                    <p class="text-xs font-medium text-gray-500 mb-1.5">Current image</p>
                                    <img src="{{ e($heroImgUrl) }}" alt="Landing hero" class="max-w-[200px] max-h-[120px] object-cover rounded-lg border border-gray-200" referrerpolicy="no-referrer" loading="lazy" onerror="this.style.display='none'; var n=this.nextElementSibling; if(n) n.style.display='block';">
                                    <p class="landing-hero-img-error text-xs text-amber-600 mt-1" style="display: none;">Image could not be loaded. Use a direct image link or upload a file.</p>
                                </div>
                            @endif
                            <div class="pt-2 border-t border-gray-200 space-y-4">
                                <div>
                                    <label for="landing_hero_image_url" class="block text-sm font-medium text-gray-700 mb-1.5">Image URL</label>
                                    <input type="url" name="landing_hero_image_url" id="landing_hero_image_url" value="{{ old('landing_hero_image_url', $landing_hero_image ?? '') }}" placeholder="https://example.com/image.jpg" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                    <p class="text-xs text-gray-500 mt-1">Paste a link to an image. Leave blank to keep current or use upload below.</p>
                                </div>
                                <div>
                                    <label for="landing_hero_image_file" class="block text-sm font-medium text-gray-700 mb-1.5">Or upload image</label>
                                    <input type="file" name="landing_hero_image_file" id="landing_hero_image_file" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 file:border file:border-gray-200">
                                    <p class="text-xs text-gray-500 mt-1">Stored on Cloudinary. Max 5 MB. If both URL and file are set, file is used.</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(session('admin_role') === 'super_admin')
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Landing page – Quiz token</h3>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="landing_show_quiz_token" value="1" {{ old('landing_show_quiz_token', $landing_show_quiz_token ?? false) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Show quiz token input on landing page</span>
                            </label>
                            <p class="text-xs text-gray-500 ml-7">When on, students see the quiz token field on the homepage. When off, the token field is hidden (students can still start quizzes via direct links or from their dashboard).</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Tab: Email -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="email" id="tab-content-email">
                    <div class="mb-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-1">Email</h2>
                        <p class="text-sm text-gray-600">Outgoing mail configuration. Stored in database (password encrypted). Used for password reset and notifications.</p>
                    </div>
                    <div class="rounded-lg border border-primary-200 bg-primary-50/80 p-4 mb-6 text-sm text-primary-800">
                        <p class="font-medium">Secure SSL/TLS (recommended)</p>
                        <p class="mt-1 text-primary-700">Host: mail.ausweblabs.com — Port: 465 (SSL). Username: reset@ausweblabs.com. Use the account password. IMAP/POP3/SMTP require authentication.</p>
                    </div>
                    <div class="space-y-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">SMTP server</h3>
                            <div>
                                <label for="mail_mailer" class="block text-sm font-medium text-gray-700 mb-1.5">Mailer</label>
                                <select name="mail_mailer" id="mail_mailer" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                    <option value="smtp" {{ ($mail_mailer ?? '') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                    <option value="sendmail" {{ ($mail_mailer ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                    <option value="log" {{ ($mail_mailer ?? '') === 'log' ? 'selected' : '' }}>Log (no send)</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-1.5">Host</label>
                                    <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', $mail_host ?? 'mail.ausweblabs.com') }}" placeholder="mail.ausweblabs.com" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                </div>
                                <div>
                                    <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-1.5">Port</label>
                                    <input type="text" name="mail_port" id="mail_port" value="{{ old('mail_port', $mail_port ?? '465') }}" placeholder="465" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                </div>
                            </div>
                            <div>
                                <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                                <input type="text" name="mail_username" id="mail_username" value="{{ old('mail_username', $mail_username ?? 'reset@ausweblabs.com') }}" placeholder="reset@ausweblabs.com" autocomplete="off" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                            </div>
                            <div>
                                <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                                <input type="password" name="mail_password" id="mail_password" autocomplete="new-password" placeholder="Leave blank to keep current" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Stored encrypted. Leave blank to keep existing password.</p>
                            </div>
                            <div>
                                <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-1.5">Encryption</label>
                                <select name="mail_encryption" id="mail_encryption" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                    <option value="tls" {{ ($mail_encryption ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ ($mail_encryption ?? 'ssl') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="" {{ ($mail_encryption ?? '') === '' ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">From (sender)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-1.5">From address</label>
                                    <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $mail_from_address ?? 'reset@ausweblabs.com') }}" placeholder="reset@ausweblabs.com" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                </div>
                                <div>
                                    <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-1.5">From name</label>
                                    <input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', $mail_from_name ?? 'QuizSnap') }}" placeholder="QuizSnap" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">Result-ready notification</h3>
                            <p class="text-sm text-gray-600">When a student finishes a quiz and submits their answers, their result is “ready.” You can get an email each time that happens so you know without opening the dashboard.</p>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="notify_result_ready" value="1" {{ old('notify_result_ready', $notify_result_ready ?? false) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="text-sm font-medium text-gray-700">Send an email when a student submits a quiz</span>
                            </label>
                            <div>
                                <label for="notify_result_email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address to notify</label>
                                <input type="email" name="notify_result_email" id="notify_result_email" value="{{ old('notify_result_email', $notify_result_email ?? '') }}" placeholder="e.g. examiner@example.com" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Who receives the notification (e.g. examiner or admin). Leave blank to turn off; one email is sent per quiz submission.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: AI -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="ai" id="tab-content-ai">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">AI Question Generation</p>
                <p class="text-sm text-gray-500 mb-4">Question generation tries <strong>Gemini (primary)</strong>, then OpenAI, then DeepSeek. Set Gemini first; others are fallbacks. Keys are stored in the database. Cloudinary (Cloudinary tab) is used for proctoring and institution logo.</p>
                <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-4">If Test fails: <strong>Gemini 429</strong> — quota exceeded, check <a href="https://aistudio.google.com/" target="_blank" rel="noopener" class="underline">AI Studio</a> billing/limits. <strong>OpenAI 429</strong> — add billing/credits at <a href="https://platform.openai.com/account/billing" target="_blank" rel="noopener" class="underline">platform.openai.com → Billing</a>. <strong>DeepSeek 402</strong> — add balance at <a href="https://platform.deepseek.com/" target="_blank" rel="noopener" class="underline">DeepSeek</a>.</p>
                <div class="space-y-5">
                    <div>
                        <label for="openai_api_key" class="block text-xs font-medium text-gray-500 mb-0.5">OpenAI API Key (fallback)</label>
                        @if($openai_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-1.5">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">{{ $openai_key_masked ?? '' }}</code></p>
                            <input type="password" name="openai_api_key" id="openai_api_key" autocomplete="off" placeholder="Enter new key to replace, or leave blank to keep" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('openai_api_key') border-red-500 @enderror">
                            <label class="flex items-center gap-2 cursor-pointer mt-1.5">
                                <input type="checkbox" name="clear_openai_key" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-gray-600">Remove OpenAI key</span>
                            </label>
                        @else
                            <input type="password" name="openai_api_key" id="openai_api_key" autocomplete="off" placeholder="sk-..." class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('openai_api_key') border-red-500 @enderror">
                            <p class="text-xs text-gray-500 mt-1">Get a key from <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener" class="text-gray-600 hover:underline">OpenAI API keys</a>. Used when Gemini is unavailable or fails. New accounts may need <a href="https://platform.openai.com/account/billing" target="_blank" rel="noopener" class="text-gray-600 hover:underline">Billing</a> set up before the key works.</p>
                        @endif
                        @error('openai_api_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="gemini_api_key" class="block text-xs font-medium text-gray-500 mb-0.5">Gemini API Key (primary)</label>
                        @if($gemini_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-1.5">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">{{ $gemini_key_masked ?? '' }}</code></p>
                            <input type="password" name="gemini_api_key" id="gemini_api_key" autocomplete="off" placeholder="Enter new key to replace, or leave blank to keep" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('gemini_api_key') border-red-500 @enderror">
                            <label class="flex items-center gap-2 cursor-pointer mt-1.5">
                                <input type="checkbox" name="clear_gemini_key" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-gray-600">Remove Gemini key</span>
                            </label>
                        @else
                            <input type="password" name="gemini_api_key" id="gemini_api_key" autocomplete="off" placeholder="AIza..." class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('gemini_api_key') border-red-500 @enderror">
                            <p class="text-xs text-gray-500 mt-1">Get a key from <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener" class="text-gray-600 hover:underline">Google AI Studio</a> (Gemini). Used first for question generation.</p>
                        @endif
                        @error('gemini_api_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="deepseek_api_key" class="block text-xs font-medium text-gray-500 mb-0.5">DeepSeek API Key (fallback)</label>
                        @if($deepseek_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-1.5">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">{{ $deepseek_key_masked ?? '' }}</code></p>
                            <input type="password" name="deepseek_api_key" id="deepseek_api_key" autocomplete="off" placeholder="Enter new key to replace, or leave blank to keep" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('deepseek_api_key') border-red-500 @enderror">
                            <label class="flex items-center gap-2 cursor-pointer mt-1.5">
                                <input type="checkbox" name="clear_deepseek_key" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-gray-600">Remove DeepSeek key</span>
                            </label>
                        @else
                            <input type="password" name="deepseek_api_key" id="deepseek_api_key" autocomplete="off" placeholder="sk-..." class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('deepseek_api_key') border-red-500 @enderror">
                            <p class="text-xs text-gray-500 mt-1">Get a key from <a href="https://platform.deepseek.com/api_keys" target="_blank" rel="noopener" class="text-gray-600 hover:underline">DeepSeek Platform</a>. Used when Gemini and OpenAI are not set or fail.</p>
                        @endif
                        @error('deepseek_api_key')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <label for="ai_quiz_cooldown_hours" class="block text-xs font-medium text-gray-500 mb-0.5">AI token auto-reset (hours)</label>
                        <input type="number" name="ai_quiz_cooldown_hours" id="ai_quiz_cooldown_hours" value="{{ old('ai_quiz_cooldown_hours', $ai_quiz_cooldown_hours ?? 24) }}" min="1" max="168" step="1" class="block w-28 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('ai_quiz_cooldown_hours') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">When tokens are exhausted, generation is blocked until this many hours pass, then tokens reset to the allocation. Allocation changes (in Users) apply on the next refill. Default: 24.</p>
                        @error('ai_quiz_cooldown_hours')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @unless(app()->environment('production'))
                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Test connection</p>
                        <p class="text-xs text-gray-500 mb-2">Tries Gemini first, then OpenAI, then DeepSeek. Save settings first if you just changed a key.</p>
                        <button type="button" id="ai-test-btn" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-300">
                            Test AI connection
                        </button>
                        <div id="ai-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                    @endunless
                </div>
                </div>

                <!-- Tab: Cloudinary -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="cloudinary" id="tab-content-cloudinary">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Cloudinary (Proctoring &amp; result photos)</p>
                <p class="text-sm text-gray-500 mb-4">Store student face photos (before and after quiz) on Cloudinary for lightweight, fast delivery. Leave blank to keep storing images locally.</p>
                <div class="space-y-5">
                    <div>
                        <label for="cloudinary_cloud_name" class="block text-xs font-medium text-gray-500 mb-0.5">Cloud name</label>
                        <input type="text" name="cloudinary_cloud_name" id="cloudinary_cloud_name" value="{{ old('cloudinary_cloud_name', $cloudinary_cloud_name ?? '') }}" placeholder="your-cloud-name" autocomplete="off" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">From your <a href="https://console.cloudinary.com/" target="_blank" rel="noopener" class="text-gray-600 hover:underline">Cloudinary dashboard</a>.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cloudinary_api_key" class="block text-xs font-medium text-gray-500 mb-0.5">API key</label>
                            @if($cloudinary_key_set ?? false)
                                <p class="text-sm text-gray-600 mb-1.5">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">{{ $cloudinary_key_masked ?? '' }}</code></p>
                                <input type="text" name="cloudinary_api_key" id="cloudinary_api_key" value="{{ old('cloudinary_api_key') }}" placeholder="Enter new key to replace, or leave blank to keep" autocomplete="off" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            @else
                                <input type="text" name="cloudinary_api_key" id="cloudinary_api_key" value="{{ old('cloudinary_api_key') }}" placeholder="123456789012345" autocomplete="off" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            @endif
                        </div>
                        <div>
                            <label for="cloudinary_api_secret" class="block text-xs font-medium text-gray-500 mb-0.5">API secret</label>
                            @if($cloudinary_secret_set ?? false)
                                <p class="text-sm text-gray-600 mb-1.5">Current secret: <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">••••••••</code> (saved)</p>
                            @endif
                            <input type="password" name="cloudinary_api_secret" id="cloudinary_api_secret" value="" placeholder="{{ ($cloudinary_secret_set ?? false) ? 'Enter new secret to replace, or leave blank to keep' : 'Enter your Cloudinary API secret' }}" autocomplete="new-password" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            <p class="text-xs text-gray-500 mt-1">Required for uploads. Stored in database and used when calling Cloudinary.</p>
                        </div>
                    </div>
                    <div>
                        <label for="cloudinary_folder" class="block text-xs font-medium text-gray-500 mb-0.5">Folder (optional)</label>
                        <input type="text" name="cloudinary_folder" id="cloudinary_folder" value="{{ old('cloudinary_folder', $cloudinary_folder ?? '') }}" placeholder="quizsnap" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">Subfolder for uploads. Default: quizsnap. Images are optimized (quality_auto, fetch_format auto) for lightweight storage.</p>
                    </div>
                    @unless(app()->environment('production'))
                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Test connection</p>
                        <p class="text-xs text-gray-500 mb-2">Save settings first, then test. Uploads a tiny test image to verify credentials.</p>
                        <button type="button" id="cloudinary-test-btn" class="inline-flex items-center justify-center rounded-md border border-transparent bg-yellow-500 px-3 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">
                            Test Cloudinary
                        </button>
                        <div id="cloudinary-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                    @endunless
                </div>
                </div>

                <!-- Tab: OTP (Arkesel) -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="otp" id="tab-content-otp">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">OTP Providers (SMS)</p>
                <p class="text-sm text-gray-500 mb-4">Configure SMS OTP delivery via <a href="https://arkesel.com" target="_blank" rel="noopener" class="text-gray-600 hover:underline">Arkesel</a>. API keys are stored encrypted. Use the test below to verify delivery.</p>
                <div class="space-y-5">
                    <div>
                        <label for="otp_arkesel_api_key" class="block text-xs font-medium text-gray-500 mb-0.5">Arkesel API Key</label>
                        @if($otp_arkesel_key_set ?? false)
                            <p class="text-sm text-gray-600 mb-1.5">Current key: <code class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">{{ $otp_arkesel_key_masked ?? '' }}</code></p>
                            <input type="password" name="otp_arkesel_api_key" id="otp_arkesel_api_key" autocomplete="off" placeholder="Enter new key to replace, or leave blank to keep" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            <label class="flex items-center gap-2 cursor-pointer mt-1.5">
                                <input type="checkbox" name="clear_otp_arkesel_key" value="1" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-gray-600">Remove Arkesel API key</span>
                            </label>
                        @else
                            <input type="password" name="otp_arkesel_api_key" id="otp_arkesel_api_key" autocomplete="off" placeholder="Your Arkesel API key" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            <p class="text-xs text-gray-500 mt-1">Get your key from <a href="https://sms.arkesel.com/dashboard" target="_blank" rel="noopener" class="text-gray-600 hover:underline">Arkesel Dashboard</a> → SMS API. Stored encrypted.</p>
                        @endif
                    </div>
                    <div>
                        <label for="otp_arkesel_sender_id" class="block text-xs font-medium text-gray-500 mb-0.5">Sender ID (optional)</label>
                        <input type="text" name="otp_arkesel_sender_id" id="otp_arkesel_sender_id" value="{{ old('otp_arkesel_sender_id', $otp_arkesel_sender_id ?? 'QuizSnap') }}" placeholder="QuizSnap" maxlength="11" class="block w-full max-w-xs rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">Max 11 characters. Shown as SMS sender (e.g. QuizSnap).</p>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Account balance</p>
                        <p class="text-xs text-gray-500 mb-2">Verify your Arkesel account has SMS credits (required for delivery).</p>
                        <button type="button" id="otp-balance-btn" class="inline-flex items-center justify-center rounded-md border border-transparent bg-yellow-500 px-3 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">Check balance</button>
                        <div id="otp-balance-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Test OTP delivery</p>
                        <p class="text-xs text-gray-500 mb-2">Save settings first if you changed the API key. Use international format (e.g. 233544919953 for Ghana). If you don’t receive the SMS, check balance above and your Arkesel dashboard for delivery status.</p>
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label for="otp-test-phone" class="block text-xs font-medium text-gray-500 mb-0.5">Phone number</label>
                                <input type="text" id="otp-test-phone" placeholder="233544919953" autocomplete="off" class="block w-48 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                            </div>
                            <button type="button" id="otp-test-btn" class="inline-flex items-center justify-center rounded-md border border-transparent bg-yellow-500 px-3 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">Send test OTP</button>
                        </div>
                        <div id="otp-test-result" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                    </div>
                </div>
                </div>

                @if($can_manage_proctoring ?? false)
                <!-- Tab: Proctoring (Super Admin only) -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="proctoring" id="tab-content-proctoring">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Quiz Proctoring &amp; Live View</p>
                <p class="text-sm text-gray-500 mb-5">Enable or disable quiz proctoring features and the live examiner view. When disabled, the system will not enforce that feature. All options are on by default.</p>

                <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-4 mb-5">
                    <p class="text-xs font-medium text-gray-500 mb-1">Live examiner view</p>
                    <p class="text-xs text-gray-500 mb-3">When on, examiners can open the &quot;Live proctor&quot; tab on a quiz to watch students in real time (camera feed). When off, the Live proctor tab and route are hidden.</p>
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="live_proctor_enabled" value="1" {{ old('live_proctor_enabled', $live_proctor_enabled ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                            <span class="text-sm text-gray-700">On</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="live_proctor_enabled" value="0" {{ old('live_proctor_enabled', $live_proctor_enabled ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                            <span class="text-sm text-gray-600">Off</span>
                        </label>
                    </div>
                </div>

                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Student-side proctoring (during quiz)</p>
                <div class="space-y-4">
                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Camera</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_camera_required" value="1" {{ old('proctoring_camera_required', $proctoring_camera_required ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">Camera required</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_camera_required" value="0" {{ old('proctoring_camera_required', $proctoring_camera_required ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Camera not required</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">When on, students must keep the camera on throughout the quiz; when off, camera is optional.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Face monitoring (out of frame / no face)</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_face_monitor" value="1" {{ old('proctoring_face_monitor', $proctoring_face_monitor ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">On</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_face_monitor" value="0" {{ old('proctoring_face_monitor', $proctoring_face_monitor ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Face monitoring off</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Detect when face is missing or out of frame; repeated violations can auto-submit.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Tab switch / blur detection</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_tab_switch" value="1" {{ old('proctoring_tab_switch', $proctoring_tab_switch ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">On</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_tab_switch" value="0" {{ old('proctoring_tab_switch', $proctoring_tab_switch ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Tab switch detection off</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Record violation when student switches tab or blurs the window.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Object detection (phone, etc.)</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_object_detect" value="1" {{ old('proctoring_object_detect', $proctoring_object_detect ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">On</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_object_detect" value="0" {{ old('proctoring_object_detect', $proctoring_object_detect ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Object detection off</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Warn when prohibited objects (e.g. phone) are detected in the camera frame.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Block right-click</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_block_right_click" value="1" {{ old('proctoring_block_right_click', $proctoring_block_right_click ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">Block right-click</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_block_right_click" value="0" {{ old('proctoring_block_right_click', $proctoring_block_right_click ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Allow right-click</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Prevent context menu and log violation on right-click.</p>
                    </div>

                    <div class="rounded-md border border-gray-100 bg-white p-3">
                        <p class="text-sm font-medium text-gray-800 mb-1">Block copy / paste</p>
                        <div class="flex flex-wrap items-center gap-4 mb-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_block_copy_paste" value="1" {{ old('proctoring_block_copy_paste', $proctoring_block_copy_paste ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-700">Block copy / paste</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="proctoring_block_copy_paste" value="0" {{ old('proctoring_block_copy_paste', $proctoring_block_copy_paste ?? true) ? '' : 'checked' }} class="h-4 w-4 rounded-full border-gray-300 text-gray-600 focus:ring-gray-300">
                                <span class="text-sm text-gray-600">Allow copy / paste</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Prevent copy, cut, and paste and log violation.</p>
                    </div>
                </div>
                </div>
                @endif
                @if($show_backup_tab ?? false)
                <!-- Tab: Backup (primary administrator only – tab hidden for secondary) -->
                <div class="settings-tab-content p-6 hidden" data-tab-content="backup" id="tab-content-backup">
                    <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-5">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 mb-1">Digest</h2>
                            <p class="text-sm text-gray-600">Optional contact for system digests and notifications.</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label for="notify_digest_recipient" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                                <input type="email" name="notify_digest_recipient" id="notify_digest_recipient" value="{{ old('notify_digest_recipient') }}" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none" placeholder="{{ ($backup_email_configured ?? false) ? '••••••@••••••' : 'e.g. user@example.com' }}" autocomplete="off">
                                @if($backup_email_configured ?? false)
                                    <p class="text-xs text-gray-500 mt-1.5">Currently set. Leave blank to clear, or enter a new address to replace.</p>
                                @else
                                    <p class="text-xs text-gray-500 mt-1.5">Enter a valid email address. Leave blank to leave unset.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-md border border-transparent bg-yellow-500 px-4 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save all settings
                </button>
            </div>
        </form>

    </div>
</div>

@push('scripts')
<script>
// Tab switching + persist tab in URL hash so refresh keeps user on same tab
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.settings-tab-btn');
    const tabContents = document.querySelectorAll('.settings-tab-content');
    const validTabs = ['general', 'email', 'ai', 'cloudinary', 'otp', 'proctoring', 'backup'];

    function switchToTab(targetTab) {
        if (!validTabs.includes(targetTab)) targetTab = 'general';
        location.hash = targetTab;
        tabBtns.forEach(function(b) {
            if (b.getAttribute('data-tab') === targetTab) {
                b.classList.add('border-primary-500', 'text-primary-600');
                b.classList.remove('border-transparent', 'text-gray-500');
            } else {
                b.classList.remove('border-primary-500', 'text-primary-600');
                b.classList.add('border-transparent', 'text-gray-500');
            }
        });
        tabContents.forEach(function(content) {
            content.classList.toggle('hidden', content.getAttribute('data-tab-content') !== targetTab);
        });
    }

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            switchToTab(this.getAttribute('data-tab'));
        });
    });

    var hash = (location.hash || '').replace(/^#/, '');
    if (validTabs.includes(hash)) {
        switchToTab(hash);
    } else {
        switchToTab('general');
    }

    var form = document.getElementById('settings-form');
    if (form) {
        form.addEventListener('submit', function() {
            var tabInput = document.getElementById('settings_tab');
            if (tabInput) tabInput.value = (location.hash || '#general').replace(/^#/, '') || 'general';
        });
    }
});

@unless(app()->environment('production'))
// Cloudinary Test
document.addEventListener('DOMContentLoaded', function() {
    var cloudinaryBtn = document.getElementById('cloudinary-test-btn');
    if (cloudinaryBtn) {
        cloudinaryBtn.addEventListener('click', function() {
            var btn = this;
            var resultEl = document.getElementById('cloudinary-test-result');
            resultEl.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            resultEl.textContent = 'Testing…';
            btn.disabled = true;
            fetch('{{ route('dashboard.settings.cloudinary-test') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
            .then(function(res) {
                var d = res.data;
                resultEl.classList.remove('hidden');
                if (d.success) {
                    resultEl.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                    resultEl.textContent = d.message || 'Cloudinary connection OK.';
                } else {
                    resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    resultEl.textContent = (d.message || 'Cloudinary test failed.') + (d.detail ? ' ' + d.detail : '');
                }
            })
            .catch(function(err) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
            })
            .finally(function() { btn.disabled = false; });
        });
    }
});

// AI Test
var aiTestBtn = document.getElementById('ai-test-btn');
if (aiTestBtn) {
    aiTestBtn.addEventListener('click', function() {
        var btn = this;
        var resultEl = document.getElementById('ai-test-result');
        resultEl.classList.remove('hidden', 'bg-green-100', 'border-green-300', 'text-green-800', 'bg-red-100', 'border-red-300', 'text-red-800');
        resultEl.textContent = 'Testing…';
        resultEl.classList.add('border', 'border-gray-200', 'text-gray-700');
        btn.disabled = true;
        var aiTestUrl = '{{ route('dashboard.settings.ai-test') }}' + '?_ts=' + Date.now();
        fetch(aiTestUrl, {
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
        .then(function(res) {
            var d = res.data;
            resultEl.classList.remove('border-gray-200', 'text-gray-700');
            resultEl.classList.remove('hidden');
            if (d.success) {
                resultEl.classList.add('bg-green-100', 'border', 'border-green-300', 'text-green-800');
                resultEl.textContent = (d.message || 'AI connection OK') + (d.provider ? ' (Provider: ' + d.provider + (d.reply ? ', reply: ' + d.reply : '') + ')' : '');
            } else {
                resultEl.classList.add('bg-red-100', 'border', 'border-red-300', 'text-red-800');
                resultEl.textContent = (d.message || 'AI connection failed') + (d.detail ? ' ' + d.detail : '');
            }
        })
        .catch(function(err) {
            resultEl.classList.remove('border-gray-200', 'text-gray-700');
            resultEl.classList.remove('hidden');
            resultEl.classList.add('bg-red-100', 'border', 'border-red-300', 'text-red-800');
            resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
        })
        .finally(function() {
            btn.disabled = false;
        });
    });
}
@endunless

// OTP Balance check & Test (available in all environments)
document.addEventListener('DOMContentLoaded', function() {
    var otpBalanceBtn = document.getElementById('otp-balance-btn');
    var otpBalanceResult = document.getElementById('otp-balance-result');
    if (otpBalanceBtn && otpBalanceResult) {
        otpBalanceBtn.addEventListener('click', function() {
            otpBalanceResult.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            otpBalanceResult.textContent = 'Checking…';
            otpBalanceBtn.disabled = true;
            fetch('{{ route('dashboard.settings.otp-balance') }}', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
                .then(function(res) {
                    var d = res.data;
                    otpBalanceResult.classList.remove('hidden');
                    if (d.success) {
                        otpBalanceResult.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                        otpBalanceResult.textContent = 'SMS balance: ' + (d.sms_balance != null ? d.sms_balance : '—') + ' | Main balance: ' + (d.main_balance != null ? d.main_balance : '—');
                    } else {
                        otpBalanceResult.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                        otpBalanceResult.textContent = d.message || 'Could not check balance.';
                    }
                })
                .catch(function(err) {
                    otpBalanceResult.classList.remove('hidden');
                    otpBalanceResult.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    otpBalanceResult.textContent = 'Request failed: ' + (err.message || 'Network error');
                })
                .finally(function() { otpBalanceBtn.disabled = false; });
        });
    }

    var otpTestBtn = document.getElementById('otp-test-btn');
    if (otpTestBtn) {
        otpTestBtn.addEventListener('click', function() {
            var phoneInput = document.getElementById('otp-test-phone');
            var resultEl = document.getElementById('otp-test-result');
            var phone = phoneInput && phoneInput.value ? phoneInput.value.trim() : '';
            if (!phone) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Enter a phone number first.';
                return;
            }
            resultEl.classList.remove('hidden', 'bg-success-50', 'border-success-200', 'text-success-800', 'bg-danger-50', 'border-danger-200', 'text-danger-800');
            resultEl.textContent = 'Sending test OTP…';
            otpTestBtn.disabled = true;
            var formData = new FormData();
            formData.append('phone', phone);
            formData.append('_token', document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value);
            fetch('{{ route('dashboard.settings.otp-test') }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
            .then(function(res) {
                var d = res.data;
                resultEl.classList.remove('hidden');
                if (d.success) {
                    resultEl.classList.add('bg-success-50', 'border', 'border-success-200', 'text-success-800');
                    resultEl.textContent = d.message || 'Test OTP sent.';
                } else {
                    resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                    resultEl.textContent = d.message || 'Failed to send test OTP.';
                }
            })
            .catch(function(err) {
                resultEl.classList.remove('hidden');
                resultEl.classList.add('bg-danger-50', 'border', 'border-danger-200', 'text-danger-800');
                resultEl.textContent = 'Request failed: ' + (err.message || 'Network error');
            })
            .finally(function() { otpTestBtn.disabled = false; });
        });
    }
});
</script>
@endpush
@endsection
