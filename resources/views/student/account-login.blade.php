@extends('layouts.app')

@section('title', 'Student Login')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Student login</h1>
            <p class="text-gray-600 text-sm mb-6">First sign in with your index number and phone (we'll send a one-time code). After you sign in, <strong>register your fingerprint or Face ID</strong> for this device. Once registered, you can sign in next time with one tap—no index or code needed.</p>

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

            {{-- Passkey login: only for users who already registered their fingerprint on this device --}}
            <div id="passkey-login-wrap" class="mt-6 pt-4 border-t border-gray-200 hidden">
                <p class="text-center text-gray-500 text-sm mb-2">Already registered your fingerprint on this device?</p>
                <button type="button" id="btn-passkey-login" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg border-2 border-primary-500 text-primary-600 bg-primary-50 hover:bg-primary-100 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 flex items-center justify-center gap-2">
                    <i class="fas fa-fingerprint" aria-hidden="true"></i>
                    Sign in with fingerprint or Face ID
                </button>
            </div>
            <div id="passkey-login-error" class="hidden mt-4">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800" id="passkey-login-error-text"></div>
            </div>

            {{-- Step 2: Phone (first-time or unregistered) --}}
            <div id="step-phone" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="phone-step-message">Enter your active phone number to receive a one-time code (e.g. 233XXXXXXXXX).</p>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone number</label>
                    <input type="tel" id="phone" name="phone" placeholder="233XXXXXXXXX" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" autocomplete="tel">
                </div>
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
    var stepIndex = document.getElementById('step-index');
    var stepPhone = document.getElementById('step-phone');
    var stepOtp = document.getElementById('step-otp');
    var indexInput = document.getElementById('index_number');
    var phoneInput = document.getElementById('phone');
    var otpInput = document.getElementById('otp_code');
    var nameInput = document.getElementById('otp_name');
    var currentIndexNumber = '';
    var lastPhoneUsed = '';

    var passkeyLoginWrap = document.getElementById('passkey-login-wrap');
    var deviceHasBiometric = false;
    var isMobileDevice = /Android|iPhone|iPod/i.test((navigator.userAgent || ''));
    function hasPasskeyCookie() {
        try {
            return document.cookie.split(';').some(function(c) {
                return c.trim().indexOf('quizsnap_has_passkey=') === 0;
            });
        } catch (e) {
            return false;
        }
    }
    // Only show passkey option when device has a user-verifying platform authenticator (fingerprint / Face ID),
    // the browser is on a mobile phone, and this device has previously registered a passkey.
    if (typeof PublicKeyCredential !== 'undefined' && typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
        PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then(function(available) {
            deviceHasBiometric = !!available;
            if (available && passkeyLoginWrap && isMobileDevice && hasPasskeyCookie()) {
                passkeyLoginWrap.classList.remove('hidden');
            }
        }).catch(function() {});
    }

    function base64urlToBuffer(str) {
        var bin = atob(str.replace(/-/g, '+').replace(/_/g, '/'));
        var buf = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);
        return buf.buffer;
    }
    function bufferToBase64url(buf) {
        var u8 = new Uint8Array(buf);
        var bin = '';
        for (var i = 0; i < u8.length; i++) bin += String.fromCharCode(u8[i]);
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function showStep(step) {
        stepIndex.classList.add('hidden');
        stepPhone.classList.add('hidden');
        stepOtp.classList.add('hidden');
        if (step === 'index') stepIndex.classList.remove('hidden');
        else if (step === 'phone') stepPhone.classList.remove('hidden');
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

    document.getElementById('btn-passkey-login').addEventListener('click', function() {
        var btn = this;
        var errWrap = document.getElementById('passkey-login-error');
        var errText = document.getElementById('passkey-login-error-text');
        if (errWrap) errWrap.classList.add('hidden');
        btn.disabled = true;
        fetch('{{ route("student.passkey.login-options") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.options || !data.options.publicKey) {
                if (errText) errText.textContent = data.message || 'Could not start passkey sign-in.';
                if (errWrap) errWrap.classList.remove('hidden');
                btn.disabled = false;
                return;
            }
            var pk = data.options.publicKey;
            var publicKey = {
                challenge: base64urlToBuffer(pk.challenge),
                timeout: pk.timeout || 60000,
                rpId: pk.rpId || window.location.hostname,
                userVerification: pk.userVerification || 'preferred'
            };
            if (pk.allowCredentials && pk.allowCredentials.length) {
                publicKey.allowCredentials = pk.allowCredentials.map(function(c) {
                    return { type: 'public-key', id: base64urlToBuffer(c.id), transports: c.transports || [] };
                });
            }
            return navigator.credentials.get({ publicKey: publicKey });
        })
        .then(function(cred) {
            btn.disabled = false;
            if (!cred) return;
            var r = cred.response;
            var assertion = {
                id: cred.id,
                rawId: bufferToBase64url(cred.rawId),
                response: {
                    clientDataJSON: bufferToBase64url(r.clientDataJSON),
                    authenticatorData: bufferToBase64url(r.authenticatorData),
                    signature: bufferToBase64url(r.signature)
                }
            };
            if (r.userHandle) assertion.response.userHandle = bufferToBase64url(r.userHandle);
            return fetch('{{ route("student.passkey.login") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ assertion: assertion })
            });
        })
        .then(function(r) { return r && r.json ? r.json() : null; })
        .then(function(data) {
            if (data && data.success && data.redirect) window.location.href = data.redirect;
            else if (data && !data.success && errText) {
                errText.textContent = data.message || 'No passkey set up for this device yet. Sign in with your index number and code above first; after signing in you can add fingerprint or Face ID for next time.';
                if (errWrap) errWrap.classList.remove('hidden');
            }
        })
        .catch(function() {
            btn.disabled = false;
            if (errText) errText.textContent = 'No passkey set up for this device yet. Sign in with your index number and code above first; after signing in you can add fingerprint or Face ID for next time.';
            if (errWrap) errWrap.classList.remove('hidden');
        });
    });

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
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
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
            if (data.step === 'phone') {
                document.getElementById('phone-step-message').textContent = data.message || 'Enter your active phone number to receive a one-time code.';
                showStep('phone');
                if (phoneInput) phoneInput.value = '';
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
                    resendBtn.textContent = (data.can_resend === false && data.days_remaining != null)
                        ? 'Resend available in ' + data.days_remaining + ' day(s)' : 'Resend code';
                }
                var daysEl = document.getElementById('otp-days-remaining');
                if (daysEl && data.days_remaining != null) {
                    daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                    daysEl.style.display = 'block';
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
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
    });

    document.getElementById('btn-send-otp').addEventListener('click', function() {
        var phone = (phoneInput && phoneInput.value) ? phoneInput.value.trim() : '';
        if (!phone) {
            showError('phone-error', 'Please enter your phone number.');
            return;
        }
        showError('phone-error', '');
        setLoading(this, true);
        this.dataset.originalText = this.textContent;
        fetch('{{ route("student.account.send-otp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ index_number: currentIndexNumber, phone: phone })
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
        fetch('{{ route("student.account.send-otp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('otp-step-message').textContent = data.message || 'A new code has been sent. Enter it above.';
                resendBtn.disabled = true;
                resendBtn.textContent = 'Resend available in ' + (data.days_remaining || 14) + ' day(s)';
                var daysEl = document.getElementById('otp-days-remaining');
                if (daysEl && data.days_remaining != null) {
                    daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                    daysEl.style.display = 'block';
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
        fetch('{{ route("student.account.verify-otp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
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
