@extends('layouts.app')

@section('title', 'Student Login')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Student login</h1>
            <p class="text-gray-600 text-sm mb-6">@if(!empty($password_login_enabled))Enter your index number. If this is your first time with a password, add your phone and choose a password; we’ll send one SMS to verify your number. After that you can sign in with your password. You can still get a code by SMS when needed.@else Use your index number and phone to sign in. We'll send a one-time code by SMS. Keep this page open while you complete the steps.@endif</p>

            {{-- Step 1: Index number (primary flow) --}}
            <div id="step-index" class="space-y-4">
                <div>
                    <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                    <input type="text" id="index_number" name="index_number" required placeholder="e.g. BC/ITS/24/047" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" style="text-transform: uppercase;" autocomplete="off">
                </div>
                <div id="index-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="index-error-text"></div>
                    <p id="index-error-support-wrap" class="hidden mt-2 text-sm text-gray-600">
                        <a id="index-error-support" href="#" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline font-medium">Get in touch</a>
                    </p>
                </div>
                <button type="button" id="btn-index" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Continue</button>
            </div>

            @if(!empty($password_login_enabled))
            {{-- Password sign-in (index already verified) --}}
            <div id="step-password" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="password-step-message">Enter your password.</p>
                <div>
                    <label for="login_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="login_password" name="login_password" autocomplete="current-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div id="password-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="password-error-text"></div>
                </div>
                <button type="button" id="btn-verify-password" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Sign in</button>
                <button type="button" id="btn-password-use-sms" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-primary-700 bg-primary-50 border border-primary-200 hover:bg-primary-100">Get a code by SMS instead</button>
                <button type="button" id="btn-back-password-to-index" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
            </div>
            @endif

            {{-- Step 2: Phone (first-time or unregistered) --}}
            <div id="step-phone" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="phone-step-message">Enter your active phone number to receive a one-time code (e.g. 233XXXXXXXXX).</p>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone number</label>
                    <input type="tel" id="phone" name="phone" placeholder="233XXXXXXXXX" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="tel">
                </div>
                @if(!empty($password_login_enabled))
                <div id="phone-password-setup-wrap" class="space-y-3 hidden">
                    <div>
                        <label for="setup_password" class="block text-sm font-medium text-gray-700 mb-1">Choose password (min 8 characters)</label>
                        <input type="password" id="setup_password" autocomplete="new-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label for="setup_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                        <input type="password" id="setup_password_confirmation" autocomplete="new-password" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                @endif
                <div id="phone-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="phone-error-text"></div>
                </div>
                <button type="button" id="btn-send-otp" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Send code</button>
                <button type="button" id="btn-back-to-index" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
            </div>

            {{-- Step 3: OTP — message first, then "Enter code" reveals the 6 boxes --}}
            <div id="step-otp" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="otp-step-message">Enter the 6-digit code sent to your phone.</p>
                <div id="otp-enter-code-wrap">
                    <button type="button" id="btn-show-otp-code" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Enter code</button>
                </div>
                <div id="otp-code-fields" class="space-y-4 hidden">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                        <div class="flex justify-center gap-2" id="otp-boxes-wrap">
                            @for($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" data-otp-index="{{ $i }}" autocomplete="off"
                                class="w-11 h-12 text-center text-xl font-semibold border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 otp-digit">
                            @endfor
                        </div>
                        <input type="hidden" id="otp_code" name="code" value="">
                    </div>
                    <div>
                        <label for="otp_name" class="block text-sm font-medium text-gray-700 mb-1">Your name (optional)</label>
                        <input type="text" id="otp_name" name="student_name" placeholder="Full name" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="name" style="text-transform: capitalize;">
                    </div>
                    <div id="otp-error" class="hidden">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="otp-error-text"></div>
                    </div>
                    <button type="button" id="btn-verify-otp" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Verify and sign in</button>
                    <p class="text-center text-sm text-gray-500">Didn't get the code? <button type="button" id="btn-resend-otp" class="text-primary-600 hover:underline font-medium">Resend code</button></p>
                    <p id="otp-days-remaining" class="text-center text-sm text-gray-500 mt-1 hidden" aria-live="polite"></p>
                    <button type="button" id="btn-back-to-phone" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-700 bg-gray-200 hover:bg-gray-300">← Back</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    var csrfRefreshUrl = '{{ route("student.account.csrf-token") }}';
    var jsonHeaders = function(token) {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token || csrf || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
    };
    function ensureFreshCsrf() {
        if (!csrfRefreshUrl) return Promise.resolve(csrf);
        return fetch(csrfRefreshUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.token) {
                    csrf = data.token;
                    var m = document.querySelector('meta[name="csrf-token"]');
                    if (m) m.setAttribute('content', csrf);
                }
                return csrf;
            });
    }
    var passwordLoginEnabled = @json(!empty($password_login_enabled));
    var stepIndex = document.getElementById('step-index');
    var stepPhone = document.getElementById('step-phone');
    var stepOtp = document.getElementById('step-otp');
    var stepPassword = document.getElementById('step-password');
    var indexInput = document.getElementById('index_number');
    var phoneInput = document.getElementById('phone');
    var otpInput = document.getElementById('otp_code');
    var nameInput = document.getElementById('otp_name');
    var setupPasswordWrap = document.getElementById('phone-password-setup-wrap');
    var currentIndexNumber = '';
    var lastPhoneUsed = '';
    var requirePasswordSetup = false;

    function showStep(step) {
        stepIndex.classList.add('hidden');
        stepPhone.classList.add('hidden');
        stepOtp.classList.add('hidden');
        if (stepPassword) stepPassword.classList.add('hidden');
        if (step === 'index') stepIndex.classList.remove('hidden');
        else if (step === 'phone') stepPhone.classList.remove('hidden');
        else if (step === 'password' && stepPassword) stepPassword.classList.remove('hidden');
        else if (step === 'otp') {
            stepOtp.classList.remove('hidden');
            var enterWrap = document.getElementById('otp-enter-code-wrap');
            var codeFields = document.getElementById('otp-code-fields');
            if (enterWrap) enterWrap.classList.remove('hidden');
            if (codeFields) codeFields.classList.add('hidden');
        }
    }

    document.getElementById('btn-show-otp-code').addEventListener('click', function() {
        var enterWrap = document.getElementById('otp-enter-code-wrap');
        var codeFields = document.getElementById('otp-code-fields');
        if (enterWrap) enterWrap.classList.add('hidden');
        if (codeFields) codeFields.classList.remove('hidden');
        initOtpBoxes();
    });

    var whatsappNumber = '233552477942';
    function supportMessage(errorText, indexNumber) {
        var msg = 'Hi, I\'m having trouble with QuizSnap login. I got this message: ' + (errorText || '') + '.';
        if (indexNumber) msg += ' My index number: ' + indexNumber + '.';
        msg += ' Can you help?';
        return encodeURIComponent(msg);
    }
    function showError(elId, text) {
        var wrap = document.getElementById(elId);
        var textEl = document.getElementById(elId + '-text');
        if (!wrap || !textEl) return;
        textEl.textContent = text || '';
        wrap.classList.toggle('hidden', !text);
        var supportWrap = document.getElementById('index-error-support-wrap');
        var supportLink = document.getElementById('index-error-support');
        if (supportWrap && supportLink && elId === 'index-error') {
            if (text) {
                supportLink.href = 'https://wa.me/' + whatsappNumber + '?text=' + supportMessage(text, (indexInput && indexInput.value) ? indexInput.value.trim() : '');
                supportWrap.classList.remove('hidden');
            } else {
                supportWrap.classList.add('hidden');
            }
        }
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        btn.disabled = loading;
        btn.dataset.originalText = btn.dataset.originalText || btn.textContent;
        btn.textContent = loading ? 'Please wait…' : (btn.dataset.originalText || 'Continue');
    }

    document.getElementById('btn-index').addEventListener('click', function() {
        var index = (indexInput && indexInput.value) ? indexInput.value.trim().toUpperCase() : '';
        if (!index) {
            showError('index-error', 'Please enter your index number.');
            return;
        }
        showError('index-error', '');
        setLoading(this, true);
        fetch('{{ route("student.account.verify-index") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(csrf),
            body: JSON.stringify({ index_number: index })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(document.getElementById('btn-index'), false);
            if (!data.success) {
                showError('index-error', data.message || 'Verification failed. Please try again.');
                var btnIndex = document.getElementById('btn-index');
                if (btnIndex) { btnIndex.dataset.originalText = 'Try again'; btnIndex.textContent = 'Try again'; }
                return;
            }
            var btnIndex = document.getElementById('btn-index');
            if (btnIndex) btnIndex.dataset.originalText = 'Continue';
            currentIndexNumber = data.index_number || index;
            requirePasswordSetup = !!(data.require_password_setup && passwordLoginEnabled);
            if (setupPasswordWrap) {
                setupPasswordWrap.classList.toggle('hidden', !requirePasswordSetup);
                if (requirePasswordSetup) {
                    var sp = document.getElementById('setup_password');
                    var spc = document.getElementById('setup_password_confirmation');
                    if (sp) sp.value = '';
                    if (spc) spc.value = '';
                }
            }
            if (data.step === 'password' && passwordLoginEnabled && stepPassword) {
                document.getElementById('password-step-message').textContent = data.message || 'Enter your password.';
                showError('password-error', '');
                var lp = document.getElementById('login_password');
                if (lp) lp.value = '';
                showStep('password');
            } else if (data.step === 'phone') {
                document.getElementById('phone-step-message').textContent = data.message || 'Enter your active phone number to receive a one-time code.';
                showStep('phone');
                if (phoneInput) {
                    phoneInput.value = (data.prefill_phone && requirePasswordSetup) ? data.prefill_phone : '';
                    phoneInput.readOnly = !!(data.prefill_phone && requirePasswordSetup);
                }
            } else if (data.step === 'otp') {
                document.getElementById('otp-step-message').textContent = data.message || 'Enter the 6-digit code sent to your phone.';
                if (data.can_resend) {
                    lastPhoneUsed = '__registered__';
                }
                if (data.has_name && nameInput) {
                    nameInput.closest('div').style.display = 'none';
                }
                var resendBtn = document.getElementById('btn-resend-otp');
                if (resendBtn) {
                    resendBtn.disabled = data.can_resend === false;
                    if (data.can_resend === false && data.days_remaining != null) {
                        resendBtn.textContent = 'Resend available in ' + data.days_remaining + ' day(s)';
                    } else {
                        resendBtn.textContent = 'Resend code';
                    }
                }
                var daysEl = document.getElementById('otp-days-remaining');
                if (daysEl) {
                    if (data.days_remaining != null) {
                        daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                        daysEl.style.display = 'block';
                    } else if (data.otp_never_expires) {
                        daysEl.textContent = 'This code does not expire until you receive a new one.';
                        daysEl.style.display = 'block';
                    }
                }
                showStep('otp');
            }
        })
        .catch(function() {
            setLoading(document.getElementById('btn-index'), false);
            showError('index-error', 'Network error. Please try again.');
            var btnIndex = document.getElementById('btn-index');
            if (btnIndex) { btnIndex.dataset.originalText = 'Try again'; btnIndex.textContent = 'Try again'; }
        });
    });

    document.getElementById('btn-back-to-index').addEventListener('click', function() {
        showStep('index');
        showError('phone-error', '');
        requirePasswordSetup = false;
        if (setupPasswordWrap) setupPasswordWrap.classList.add('hidden');
        if (phoneInput) phoneInput.readOnly = false;
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
    });

    if (passwordLoginEnabled && document.getElementById('btn-verify-password')) {
        document.getElementById('btn-verify-password').addEventListener('click', function() {
            var pw = document.getElementById('login_password');
            var v = pw && pw.value ? pw.value : '';
            if (!v) {
                showError('password-error', 'Please enter your password.');
                return;
            }
            showError('password-error', '');
            setLoading(this, true);
            var verifyPwUrl = '{{ route("student.account.verify-password") }}';
            function doPw() {
                return fetch(verifyPwUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber, password: v })
                });
            }
            ensureFreshCsrf().then(function() { return doPw(); })
            .then(function(r) {
                if (r.status === 419) return ensureFreshCsrf().then(function() { return doPw(); });
                return r;
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-verify-password'), false);
                if (!data.success) {
                    showError('password-error', data.message || 'Sign-in failed.');
                    return;
                }
                if (data.redirect) window.location.href = data.redirect;
            })
            .catch(function() {
                setLoading(document.getElementById('btn-verify-password'), false);
                showError('password-error', 'Network error. Please try again.');
            });
        });
    }
    if (passwordLoginEnabled && document.getElementById('btn-password-use-sms')) {
        document.getElementById('btn-password-use-sms').addEventListener('click', function() {
            if (!currentIndexNumber) return;
            showError('password-error', '');
            setLoading(this, true);
            ensureFreshCsrf().then(function() {
                return fetch('{{ route("student.account.request-otp-login") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: jsonHeaders(csrf),
                    body: JSON.stringify({ index_number: currentIndexNumber })
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setLoading(document.getElementById('btn-password-use-sms'), false);
                if (!data.success) {
                    showError('password-error', data.message || 'Could not send SMS.');
                    return;
                }
                document.getElementById('otp-step-message').textContent = data.message || 'Enter the code from your phone.';
                if (data.has_name && nameInput) nameInput.closest('div').style.display = 'none';
                showStep('otp');
            })
            .catch(function() {
                setLoading(document.getElementById('btn-password-use-sms'), false);
                showError('password-error', 'Network error.');
            });
        });
    }
    if (passwordLoginEnabled && document.getElementById('btn-back-password-to-index')) {
        document.getElementById('btn-back-password-to-index').addEventListener('click', function() {
            showStep('index');
            showError('password-error', '');
        });
    }

    document.getElementById('btn-send-otp').addEventListener('click', function() {
        var phone = (phoneInput && phoneInput.value) ? phoneInput.value.trim() : '';
        if (!phone) {
            showError('phone-error', 'Please enter your phone number.');
            return;
        }
        showError('phone-error', '');
        setLoading(this, true);
        this.dataset.originalText = this.textContent;
        var sendBody = { index_number: currentIndexNumber, phone: phone };
        if (requirePasswordSetup) {
            var sp = document.getElementById('setup_password');
            var spc = document.getElementById('setup_password_confirmation');
            sendBody.new_password = sp ? sp.value : '';
            sendBody.new_password_confirmation = spc ? spc.value : '';
            if (!sendBody.new_password || sendBody.new_password.length < 8) {
                showError('phone-error', 'Choose a password of at least 8 characters.');
                setLoading(document.getElementById('btn-send-otp'), false);
                return;
            }
            if (sendBody.new_password !== sendBody.new_password_confirmation) {
                showError('phone-error', 'Password confirmation does not match.');
                setLoading(document.getElementById('btn-send-otp'), false);
                return;
            }
        }
        ensureFreshCsrf().then(function() {
            return fetch('{{ route("student.account.send-otp") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(sendBody)
            });
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(document.getElementById('btn-send-otp'), false);
            if (!data.success) {
                showError('phone-error', data.message || 'We couldn\'t send the code. Please try again.');
                var sendBtn = document.getElementById('btn-send-otp');
                if (sendBtn) { sendBtn.dataset.originalText = 'Try again'; sendBtn.textContent = 'Try again'; }
                return;
            }
            lastPhoneUsed = phone;
            document.getElementById('otp-step-message').textContent = data.message || 'Enter the 6-digit code sent to your number.';
            // Hide name field if student already has a name
            if (data.has_name && nameInput) {
                nameInput.closest('div').style.display = 'none';
            }
            showStep('otp');
            showError('otp-error', '');
        })
        .catch(function() {
            setLoading(document.getElementById('btn-send-otp'), false);
            showError('phone-error', 'Network error. Please try again.');
            var sendBtn = document.getElementById('btn-send-otp');
            if (sendBtn) { sendBtn.dataset.originalText = 'Try again'; sendBtn.textContent = 'Try again'; }
        });
    });

    document.getElementById('btn-back-to-phone').addEventListener('click', function() {
        showStep('phone');
        showError('otp-error', '');
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
    });

    document.getElementById('btn-resend-otp').addEventListener('click', function() {
        if (!currentIndexNumber) {
            showError('otp-error', 'Go back and enter your index number, then try again.');
            return;
        }
        var resendBtn = document.getElementById('btn-resend-otp');
        if (resendBtn.disabled) return;
        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending…';
        showError('otp-error', '');
        var payload = { index_number: currentIndexNumber };
        if (lastPhoneUsed && lastPhoneUsed !== '__registered__') {
            payload.phone = lastPhoneUsed;
        }
        ensureFreshCsrf().then(function() {
            return fetch('{{ route("student.account.send-otp") }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(payload)
            });
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('otp-step-message').textContent = data.message || 'A new code has been sent. Enter it above.';
                resendBtn.disabled = true;
                resendBtn.textContent = 'Wait ~1 min to resend';
                setTimeout(function() {
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend code';
                }, 65000);
                var daysEl = document.getElementById('otp-days-remaining');
                if (daysEl) {
                    if (data.days_remaining != null) {
                        daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                        daysEl.style.display = 'block';
                    } else if (data.otp_never_expires) {
                        daysEl.textContent = 'This code does not expire until you receive a new one.';
                        daysEl.style.display = 'block';
                    }
                }
            } else {
                resendBtn.disabled = data.can_resend === false;
                resendBtn.textContent = (data.can_resend === false && data.days_remaining != null)
                    ? 'Resend available in ' + data.days_remaining + ' day(s)' : 'Resend code';
                showError('otp-error', data.message || 'Could not resend. Please try again.');
            }
        })
        .catch(function() {
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend code';
            showError('otp-error', 'Network error. Please try again.');
        });
    });

    function getOtpCode() {
        var boxes = document.querySelectorAll('.otp-digit');
        var code = '';
        for (var i = 0; i < (boxes.length || 6); i++) {
            if (boxes[i]) code += (boxes[i].value || '').trim();
        }
        return code;
    }
    function setOtpHidden(val) {
        var h = document.getElementById('otp_code');
        if (h) h.value = val;
    }
    function initOtpBoxes() {
        var boxes = document.querySelectorAll('.otp-digit');
        setOtpHidden('');
        boxes.forEach(function(b) { b.value = ''; });
        if (boxes[0]) boxes[0].focus();

        function syncAndMaybeSubmit() {
            var code = getOtpCode();
            setOtpHidden(code);
            if (code.length === 6) {
                var btn = document.getElementById('btn-verify-otp');
                if (btn && !btn.disabled) btn.click();
            }
        }
        boxes.forEach(function(box, i) {
            box.onkeydown = function(e) {
                if (/^[0-9]$/.test(e.key)) {
                    e.preventDefault();
                    this.value = e.key;
                    if (boxes[i + 1]) boxes[i + 1].focus();
                    else this.blur();
                    syncAndMaybeSubmit();
                    return;
                }
                if (e.key === 'Backspace' && !this.value && boxes[i - 1]) {
                    e.preventDefault();
                    boxes[i - 1].focus();
                }
            };
            box.oninput = function() {
                var v = this.value.replace(/\D/g, '').slice(0, 1);
                this.value = v;
                if (v && boxes[i + 1]) boxes[i + 1].focus();
                syncAndMaybeSubmit();
            };
            box.onpaste = function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                for (var j = 0; j < pasted.length && j < boxes.length; j++) {
                    boxes[j].value = pasted[j];
                }
                if (pasted.length > 0 && boxes[pasted.length - 1]) boxes[pasted.length - 1].focus();
                syncAndMaybeSubmit();
            };
        });
    }

    document.getElementById('btn-verify-otp').addEventListener('click', function() {
        var code = getOtpCode();
        if (!code || code.length !== 6) {
            showError('otp-error', 'Please enter the 6-digit code.');
            return;
        }
        showError('otp-error', '');
        setLoading(this, true);
        this.dataset.originalText = this.textContent;
        var payload = { index_number: currentIndexNumber, code: code };
        if (nameInput && nameInput.value.trim()) payload.student_name = nameInput.value.trim();
        var verifyUrl = '{{ route("student.account.verify-otp") }}';
        function doVerify() {
            return fetch(verifyUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: jsonHeaders(csrf),
                body: JSON.stringify(payload)
            });
        }
        ensureFreshCsrf().then(function() { return doVerify(); })
        .then(function(r) {
            if (r.status === 419) {
                return ensureFreshCsrf().then(function() { return doVerify(); });
            }
            return r;
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(document.getElementById('btn-verify-otp'), false);
            if (!data.success) {
                showError('otp-error', data.message || 'Invalid or expired code.');
                return;
            }
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        })
        .catch(function() {
            setLoading(document.getElementById('btn-verify-otp'), false);
            showError('otp-error', 'Network error. Please try again.');
        });
    });
})();
</script>
@endpush
@endsection
