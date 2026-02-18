@extends('layouts.student')

@section('title', 'Enter your index number')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))]">
    <div class="max-w-md w-full">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Enter your index number</h1>
            @if(isset($quiz) && $quiz)
                <p class="text-gray-600 text-sm mb-2">{{ $quiz->title }}</p>
            @endif

            {{-- Step 1: Index number --}}
            <div id="step-index" class="space-y-4">
                <p class="text-gray-600 text-sm mb-4">Enter your index number to continue.</p>
                <form id="login-form" class="space-y-4">
                    <div>
                        <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                        <input type="text" id="index_number" name="index_number" required placeholder="e.g. BC/ITS/24/047" class="input w-full" style="text-transform: uppercase;" autocomplete="off">
                    </div>
                    <div id="login-error" class="hidden">
                        <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="login-error-text"></div>
                        <p id="login-error-support-wrap" class="hidden mt-2 text-sm text-gray-600">
                            <a id="login-error-support" href="#" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline font-medium">Get in touch</a>
                        </p>
                    </div>
                    <button type="submit" class="btn btn-action w-full py-2.5 text-sm font-semibold">
                        <span id="btn-text">Continue</span>
                    </button>
                </form>
            </div>

            {{-- Step 2: Phone (required before first quiz; we save it to your index for future logins) --}}
            <div id="step-phone" class="space-y-4 hidden">
                <div class="rounded-lg bg-primary-50 border border-primary-200 p-3 mb-2 text-sm text-primary-900">
                    <p class="font-medium mb-1">Use an active phone number</p>
                    <p>We'll send a one-time code by SMS. <strong>Keep that code—it will be your login for the next 14 days</strong> so you can open your dashboard and see your results. We'll also save your phone and name to your index for future logins.</p>
                </div>
                <p class="text-sm text-gray-600" id="phone-step-message">Enter your active phone number (e.g. 233XXXXXXXXX).</p>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone number</label>
                    <input type="tel" id="phone" name="phone" placeholder="233XXXXXXXXX" class="input w-full" autocomplete="tel">
                </div>
                <div id="phone-error" class="hidden">
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="phone-error-text"></div>
                </div>
                <button type="button" id="btn-send-otp" class="btn btn-action w-full py-2.5 text-sm font-semibold">Send code</button>
                <button type="button" id="btn-back-to-index" class="w-full py-2 text-sm text-gray-600 hover:text-gray-800">← Back</button>
            </div>

            {{-- Step 3: OTP --}}
            <div id="step-otp" class="space-y-4 hidden">
                <p class="text-sm text-gray-600" id="otp-step-message">Enter the 6-digit code sent to your phone. Keep it—it's your login for the next 14 days.</p>
                <div>
                    <label for="otp_code" class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                    <input type="text" id="otp_code" name="code" placeholder="000000" maxlength="6" pattern="[0-9]*" inputmode="numeric" class="input w-full text-center tracking-widest text-lg" autocomplete="one-time-code">
                </div>
                <div>
                    <label for="otp_name" class="block text-sm font-medium text-gray-700 mb-1">Your name (optional)</label>
                    <input type="text" id="otp_name" name="student_name" placeholder="Full name" class="input w-full" autocomplete="name" style="text-transform: capitalize;">
                </div>
                <div id="otp-error" class="hidden">
                    <div class="bg-danger-50 border border-danger-200 rounded-lg p-3 text-sm text-danger-800" id="otp-error-text"></div>
                </div>
                <button type="button" id="btn-verify-otp" class="btn btn-action w-full py-2.5 text-sm font-semibold">Verify and continue</button>
                <p class="text-center text-sm text-gray-500">Didn't get the code? <button type="button" id="btn-resend-otp" class="text-primary-600 hover:underline font-medium">Resend code</button></p>
                <button type="button" id="btn-back-to-phone" class="w-full py-2 text-sm text-gray-600 hover:text-gray-800">← Back</button>
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

    function showStep(step) {
        stepIndex.classList.add('hidden');
        stepPhone.classList.add('hidden');
        stepOtp.classList.add('hidden');
        if (step === 'index') stepIndex.classList.remove('hidden');
        else if (step === 'phone') stepPhone.classList.remove('hidden');
        else if (step === 'otp') stepOtp.classList.remove('hidden');
    }

    var whatsappNumber = '2332541069241';
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
        var supportWrap = document.getElementById('login-error-support-wrap');
        var supportLink = document.getElementById('login-error-support');
        if (supportWrap && supportLink && elId === 'login-error') {
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

    document.getElementById('login-form').addEventListener('submit', function(e) {
        e.preventDefault();
        showError('login-error', '');
        var btn = this.querySelector('button[type="submit"]');
        var btnText = document.getElementById('btn-text');
        setLoading(btn, true);
        btnText.textContent = 'Verifying...';
        fetch('{{ route("student.verify.index") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ index_number: (indexInput && indexInput.value) ? indexInput.value.trim().toUpperCase() : '' })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(btn, false);
            btnText.textContent = 'Continue';
            if (!data.success) {
                showError('login-error', data.message || 'Verification failed.');
                return;
            }
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            currentIndexNumber = data.index_number || (indexInput && indexInput.value ? indexInput.value.trim().toUpperCase() : '');
            if (data.step === 'phone') {
                var msgEl = document.getElementById('phone-step-message');
                if (msgEl) msgEl.textContent = data.message || 'Enter your active phone number to receive an SMS.';
                showStep('phone');
                if (phoneInput) phoneInput.value = '';
            } else if (data.step === 'otp') {
                var otpMsg = document.getElementById('otp-step-message');
                if (otpMsg) otpMsg.textContent = data.message || 'Enter the 6-digit code sent to your phone.';
                // Registered-phone login can resend without phone step
                if (data.can_resend) {
                    lastPhoneUsed = '__registered__';
                }
                // Hide name field if student already has a name
                if (data.has_name && nameInput) {
                    nameInput.closest('div').style.display = 'none';
                }
                showStep('otp');
                if (otpInput) { otpInput.value = ''; otpInput.focus(); }
            }
        })
        .catch(function() {
            setLoading(btn, false);
            btnText.textContent = 'Continue';
            showError('login-error', 'Network error. Please try again.');
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
                return;
            }
            lastPhoneUsed = phone;
            var otpMsg = document.getElementById('otp-step-message');
            if (otpMsg) otpMsg.textContent = data.message || 'Enter the 6-digit code sent to your number. Keep it for 24h login.';
            // Hide name field if student already has a name
            if (data.has_name && nameInput) {
                nameInput.closest('div').style.display = 'none';
            }
            showStep('otp');
            if (otpInput) { otpInput.value = ''; otpInput.focus(); }
            showError('otp-error', '');
        })
        .catch(function() {
            setLoading(document.getElementById('btn-send-otp'), false);
            showError('phone-error', 'Network error. Please try again.');
        });
    });

    document.getElementById('btn-back-to-phone').addEventListener('click', function() {
        showStep('phone');
        showError('otp-error', '');
    });

    document.getElementById('btn-resend-otp').addEventListener('click', function() {
        if (!currentIndexNumber) {
            showError('otp-error', 'Go back and enter your index number, then try again.');
            return;
        }
        var resendBtn = document.getElementById('btn-resend-otp');
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
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend code';
            if (data.success) {
                document.getElementById('otp-step-message').textContent = 'A new code has been sent. Enter it above.';
            } else {
                showError('otp-error', data.message || 'Could not resend. Please try again.');
            }
        })
        .catch(function() {
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend code';
            showError('otp-error', 'Network error. Please try again.');
        });
    });

    document.getElementById('btn-verify-otp').addEventListener('click', function() {
        var code = (otpInput && otpInput.value) ? otpInput.value.trim() : '';
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
            if (data.redirect) window.location.href = data.redirect;
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
