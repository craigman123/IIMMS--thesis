{{-- resources/views/auth/verify-login-otp.blade.php --}}

@extends('layouts.base')

@section('title', 'SIIMMS | Verify Your Identity')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login/login.css') }}">
    <style>
        /* ── OTP page — inherits login.css variables ── */
        .otp-card {
            max-width: 420px;
            margin: 0 auto;
            background: var(--card-bg, #fff);
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(0,0,0,.14);
            overflow: hidden;
            position: relative;
        }

        .otp-card-accent {
            height: 4px;
            background: linear-gradient(90deg, #1a3557 0%, #c9a84c 100%);
        }

        .otp-card-body {
            padding: 40px 44px 36px;
        }

        .otp-icon {
            width: 56px;
            height: 56px;
            background: #eef2ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .otp-icon svg {
            width: 28px;
            height: 28px;
            stroke: #1a3557;
        }

        .otp-title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 8px;
        }

        .otp-subtitle {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin: 0 0 32px;
        }

        .otp-subtitle strong {
            color: #1a3557;
        }

        /* Digit boxes */
        .otp-digits {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 8px;
        }

        .otp-digit {
            width: 46px;
            height: 56px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            border: 2px solid #d1d5db;
            border-radius: 10px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #f9fafb;
        }

        .otp-digit:focus {
            border-color: #1a3557;
            box-shadow: 0 0 0 3px rgba(26,53,87,.12);
            background: #fff;
        }

        /* Error / info message */
        .otp-message {
            min-height: 22px;
            text-align: center;
            font-size: 13px;
            margin: 10px 0 20px;
            color: #e24b4a;
        }

        .otp-message.info {
            color: #6b7280;
        }

        .btn-verify {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: #1a3557;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
            letter-spacing: .3px;
        }

        .btn-verify:hover   { opacity: .88; }
        .btn-verify:active  { transform: scale(.98); }
        .btn-verify:disabled { opacity: .5; cursor: not-allowed; }

        .otp-footer-text {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            margin-top: 18px;
        }

        .otp-footer-text a,
        .otp-resend-btn {
            background: none;
            border: none;
            padding: 0;
            color: #2563eb;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
        }

        .otp-resend-btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .otp-resend-timer {
            display: none;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            margin-top: 6px;
        }

        .otp-back {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 14px;
            font-size: 13px;
            color: #6b7280;
            text-decoration: none;
            transition: color .15s;
        }

        .otp-back:hover { color: #1a3557; }

        .expire-notice {
            background: #fef9ec;
            border-left: 4px solid #f59e0b;
            border-radius: 4px;
            padding: 10px 14px;
            font-size: 13px;
            color: #92400e;
            margin-bottom: 24px;
        }

        /* Laravel session flash errors */
        .flash-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 14px;
            color: #b91c1c;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
@endpush

@section('content')

    <div class="bg-pattern"></div>

    <div class="otp-card">
        <div class="otp-card-accent"></div>

        <div class="otp-card-body">

            <div class="otp-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>

            <h1 class="otp-title">Check your email</h1>

            <p class="otp-subtitle">
                We sent a 6-digit verification code to<br>
                @if (session('otp_email_hint'))
                    <strong>{{ session('otp_email_hint') }}</strong>
                @else
                    your registered email address
                @endif
                .<br>Enter it below to complete sign-in.
            </p>

            <div class="expire-notice">
                ⏱ This code expires in <strong>10 minutes</strong>. Do not share it with anyone.
            </div>

            {{-- Laravel validation errors (wrong code, expired, too many attempts) --}}
            @if ($errors->any())
                <div class="flash-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.otp.confirm') }}" id="otp-form">
                @csrf

                <div class="otp-digits" id="otp-digits">
                    {{-- 6 digit boxes built by JS below --}}
                </div>

                {{-- Hidden input carries the assembled OTP value --}}
                <input type="hidden" name="otp" id="otp-hidden">

                <p class="otp-message" id="otp-msg"></p>

                <button type="submit" class="btn-verify" id="otp-submit-btn">
                    Verify &amp; Sign In
                </button>
            </form>

            <p class="otp-footer-text">
                Didn't receive it?
                <button type="button" class="otp-resend-btn" id="otp-resend-btn">
                    Resend code
                </button>
            </p>
            <p class="otp-resend-timer" id="otp-resend-timer"></p>

            <a href="{{ route('login') }}" class="otp-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Back to Sign In
            </a>

        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    // ── Build digit boxes ──────────────────────────────────────────
    const container = document.getElementById('otp-digits');
    const inputs    = [];

    for (let i = 0; i < 6; i++) {
        const inp = document.createElement('input');
        inp.type        = 'text';
        inp.inputMode   = 'numeric';
        inp.maxLength   = 1;
        inp.className   = 'otp-digit';
        inp.autocomplete = i === 0 ? 'one-time-code' : 'off';
        inp.addEventListener('input',   (e) => onInput(e, i));
        inp.addEventListener('keydown', (e) => onKeydown(e, i));
        inp.addEventListener('paste',   onPaste);
        container.appendChild(inp);
        inputs.push(inp);
    }

    function getValue() { return inputs.map(i => i.value).join(''); }
    function clear()    { inputs.forEach(i => i.value = ''); }

    function onInput(e, idx) {
        const v = e.target.value.replace(/\D/g, '');
        e.target.value = v.slice(-1);
        if (v && idx < 5) inputs[idx + 1].focus();
    }

    function onKeydown(e, idx) {
        if (e.key === 'Backspace' && !inputs[idx].value && idx > 0) {
            inputs[idx - 1].focus();
        }
    }

    function onPaste(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData)
            .getData('text').replace(/\D/g, '').slice(0, 6);
        pasted.split('').forEach((ch, i) => { if (inputs[i]) inputs[i].value = ch; });
        inputs[Math.min(pasted.length, 5)]?.focus();
    }

    // Focus first box on load
    setTimeout(() => inputs[0]?.focus(), 80);

    // ── Form submit — assemble hidden input ───────────────────────
    const form      = document.getElementById('otp-form');
    const hidden    = document.getElementById('otp-hidden');
    const msg       = document.getElementById('otp-msg');
    const submitBtn = document.getElementById('otp-submit-btn');

    form.addEventListener('submit', (e) => {
        const otp = getValue();
        if (otp.length < 6) {
            e.preventDefault();
            msg.textContent  = 'Please enter all 6 digits.';
            msg.className    = 'otp-message';
            inputs[otp.length]?.focus();
            return;
        }
        hidden.value = otp;
        submitBtn.disabled   = true;
        submitBtn.textContent = 'Verifying…';
    });

    // ── Resend OTP (AJAX) ─────────────────────────────────────────
    const resendBtn   = document.getElementById('otp-resend-btn');
    const resendTimer = document.getElementById('otp-resend-timer');
    let   timerInterval;

    function startCooldown(seconds = 60) {
        resendBtn.disabled   = true;
        resendTimer.style.display = 'block';
        let remaining = seconds;
        const tick = () => {
            resendTimer.textContent = `You can resend in ${remaining}s`;
            if (remaining-- <= 0) {
                clearInterval(timerInterval);
                resendBtn.disabled        = false;
                resendTimer.style.display = 'none';
            }
        };
        tick();
        timerInterval = setInterval(tick, 1000);
    }

    // Start cooldown immediately (OTP was just sent by the login form)
    startCooldown(60);

    resendBtn.addEventListener('click', async () => {
        msg.className   = 'otp-message info';
        msg.textContent = 'Sending a new code…';

        try {
            const res  = await fetch('{{ route('login.otp.resend') }}', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept':       'application/json',
                },
            });
            const data = await res.json();

            if (data.sent) {
                msg.className   = 'otp-message info';
                msg.textContent = data.message || 'New code sent — check your inbox.';
                clear();
                inputs[0].focus();
                startCooldown(60);
            } else {
                msg.className   = 'otp-message';
                msg.textContent = data.message || 'Could not resend. Please try again.';
            }
        } catch {
            msg.className   = 'otp-message';
            msg.textContent = 'Network error. Please try again.';
        }
    });
})();
</script>
@endpush
