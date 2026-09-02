// ─── Tab Switching ───────────────────────────────────────────────
function switchTab(tab) {
    document.getElementById('panel-login').classList.toggle('active', tab === 'login');
    document.getElementById('panel-register').classList.toggle('active', tab === 'register');
    document.getElementById('tab-login').classList.toggle('active', tab === 'login');
    document.getElementById('tab-register').classList.toggle('active', tab === 'register');
}

// ─── Email Format Check ───────────────────────────────────────────
function isValidEmailFormat(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
}

// ─── Email Status Icon/Hint ───────────────────────────────────────
function getHintId(statusId) {
    return statusId.replace('-status', '-hint');
}

function setEmailStatus(statusId, state, message = '') {
    const icon = document.getElementById(statusId);
    const hint = document.getElementById(getHintId(statusId));
    if (!icon || !hint) return;

    icon.className = 'email-status-icon';
    hint.textContent = message;
    hint.className = 'email-hint';

    if (state === 'checking') {
        icon.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>`;
        hint.classList.add('hint-checking');
    } else if (state === 'valid') {
        icon.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22863a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;
        hint.classList.add('hint-valid');
    } else if (state === 'invalid') {
        icon.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e24b4a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`;
        hint.classList.add('hint-invalid');
    } else {
        icon.innerHTML = '';
    }
}

// ─── CSRF helper ──────────────────────────────────────────────────
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';
}

// ─── OTP State ────────────────────────────────────────────────────
// Tracks which email has been verified in this page session so the
// register form can check before submitting.
const otpState = {
    verifiedEmail: null,   // email string once confirmed, else null
    pendingEmail:  null,   // email currently waiting for OTP entry
};

const requireOtpVerification = window.requireOtpVerification ?? true;

// Hydrate verification state from the server session (survives full page
// reloads, e.g. after a failed "Create Account" validation error).
if (window.otpVerifiedEmail) {
    otpState.verifiedEmail = window.otpVerifiedEmail;
}

// ─── OTP Modal ────────────────────────────────────────────────────
// Injects a lightweight modal into the DOM on first use.

function ensureOtpModal() {
    if (document.getElementById('otp-modal')) return;

    const modal = document.createElement('div');
    modal.id = 'otp-modal';
    modal.style.cssText = [
        'position:fixed;inset:0;z-index:9999',
        'display:flex;align-items:center;justify-content:center',
        'background:rgba(0,0,0,.55);backdrop-filter:blur(3px)',
    ].join(';');

    modal.innerHTML = `
        <div id="otp-box" style="
            background:#fff;border-radius:14px;padding:36px 32px;
            width:340px;max-width:90vw;box-shadow:0 8px 32px rgba(0,0,0,.18);
            font-family:inherit;position:relative;
        ">
            <button id="otp-close" aria-label="Close" style="
                position:absolute;top:14px;right:16px;background:none;
                border:none;cursor:pointer;font-size:20px;color:#6b7280;
            ">&#x2715;</button>

            <h2 style="margin:0 0 6px;font-size:18px;color:#111827;">Verify your email</h2>
            <p id="otp-subtitle" style="margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.5;"></p>

            <!-- OTP input: 6 individual digit boxes -->
            <div id="otp-digits" style="display:flex;gap:8px;justify-content:center;margin-bottom:16px;">
            </div>

            <p id="otp-msg" style="min-height:20px;margin:0 0 16px;font-size:13px;text-align:center;color:#e24b4a;"></p>

            <button id="otp-submit-btn" style="
                width:100%;padding:11px;border:none;border-radius:8px;
                background:#1a3a5c;color:#fff;font-size:15px;font-weight:600;
                cursor:pointer;transition:opacity .2s;
            ">Verify</button>

            <p style="margin:14px 0 0;text-align:center;font-size:13px;color:#6b7280;">
                Didn't receive it?
                <button id="otp-resend-btn" style="
                    background:none;border:none;color:#2563eb;cursor:pointer;
                    font-size:13px;padding:0;font-weight:600;
                ">Resend code</button>
            </p>

            <p id="otp-resend-timer" style="
                margin:6px 0 0;text-align:center;font-size:12px;color:#9ca3af;display:none;
            "></p>
        </div>
    `;

    document.body.appendChild(modal);

    // Build 6 digit input boxes
    const digitsContainer = document.getElementById('otp-digits');
    for (let i = 0; i < 6; i++) {
        const inp = document.createElement('input');
        inp.type = 'text';
        inp.inputMode = 'numeric';
        inp.maxLength = 1;
        inp.dataset.idx = i;
        inp.style.cssText = [
            'width:40px;height:48px;text-align:center;font-size:22px;font-weight:700',
            'border:2px solid #d1d5db;border-radius:8px;outline:none',
            'transition:border-color .15s;color:#111827',
        ].join(';');
        inp.addEventListener('focus', () => inp.style.borderColor = '#2563eb');
        inp.addEventListener('blur',  () => inp.style.borderColor = '#d1d5db');
        inp.addEventListener('input', (e) => onOtpDigitInput(e, i));
        inp.addEventListener('keydown', (e) => onOtpDigitKeydown(e, i));
        inp.addEventListener('paste', onOtpPaste);
        digitsContainer.appendChild(inp);
    }

    document.getElementById('otp-close').addEventListener('click', closeOtpModal);
    document.getElementById('otp-submit-btn').addEventListener('click', submitOtp);
    document.getElementById('otp-resend-btn').addEventListener('click', () => {
        if (otpState.pendingEmail) sendOtp(otpState.pendingEmail);
    });

    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeOtpModal();
    });
}

function getOtpDigits() {
    return [...document.querySelectorAll('#otp-digits input')];
}

function getOtpValue() {
    return getOtpDigits().map(i => i.value).join('');
}

function clearOtpDigits() {
    getOtpDigits().forEach(i => i.value = '');
}

function onOtpDigitInput(e, idx) {
    const inputs = getOtpDigits();
    const val = e.target.value.replace(/\D/g, '');
    e.target.value = val.slice(-1); // keep only last digit
    if (val && idx < 5) inputs[idx + 1].focus();
}

function onOtpDigitKeydown(e, idx) {
    const inputs = getOtpDigits();
    if (e.key === 'Backspace' && !inputs[idx].value && idx > 0) {
        inputs[idx - 1].focus();
    }
    if (e.key === 'Enter') submitOtp();
}

function onOtpPaste(e) {
    e.preventDefault();
    const pasted = (e.clipboardData || window.clipboardData)
        .getData('text').replace(/\D/g, '').slice(0, 6);
    const inputs = getOtpDigits();
    pasted.split('').forEach((ch, i) => { if (inputs[i]) inputs[i].value = ch; });
    const next = inputs[Math.min(pasted.length, 5)];
    if (next) next.focus();
}

function openOtpModal(email) {
    ensureOtpModal();
    otpState.pendingEmail = email;
    clearOtpDigits();
    document.getElementById('otp-msg').textContent = '';
    document.getElementById('otp-subtitle').textContent =
        `We sent a 6-digit code to ${email}. Enter it below to verify your address.`;
    document.getElementById('otp-modal').style.display = 'flex';
    setTimeout(() => getOtpDigits()[0]?.focus(), 80);
}

function closeOtpModal() {
    const modal = document.getElementById('otp-modal');
    if (modal) modal.style.display = 'none';
}

// ─── Resend cooldown timer ────────────────────────────────────────
let resendTimerInterval = null;

function startResendCooldown(seconds = 60) {
    const btn   = document.getElementById('otp-resend-btn');
    const timer = document.getElementById('otp-resend-timer');
    if (!btn || !timer) return;

    btn.disabled = true;
    btn.style.opacity = '.4';
    timer.style.display = 'block';

    let remaining = seconds;
    const tick = () => {
        timer.textContent = `You can resend in ${remaining}s`;
        if (remaining-- <= 0) {
            clearInterval(resendTimerInterval);
            btn.disabled = false;
            btn.style.opacity = '1';
            timer.style.display = 'none';
        }
    };
    tick();
    resendTimerInterval = setInterval(tick, 1000);
}

// ─── Send OTP via POST /auth/send-otp ────────────────────────────
async function sendOtp(email) {
    setEmailStatus('reg-email-status', 'checking', 'Sending verification code…');

    try {
        const res = await fetch('/auth/send-otp', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  getCsrfToken(),
                'Accept':        'application/json',
            },
            body: JSON.stringify({ email }),
        });

        const data = await res.json();

        if (res.status === 429) {
            // cooldown message from server
            setEmailStatus('reg-email-status', 'invalid', data.message);
            return;
        }

        if (!data.sent) {
            setEmailStatus('reg-email-status', 'invalid',
                data.message || 'Could not send verification email.');
            return;
        }

        // Success → open modal, start cooldown
        setEmailStatus('reg-email-status', 'checking', 'Code sent — check your inbox.');
        openOtpModal(email);
        startResendCooldown(60);

    } catch {
        setEmailStatus('reg-email-status', 'invalid',
            'Network error. Please try again.');
    }
}

// ─── Confirm OTP via POST /auth/confirm-otp ──────────────────────
async function submitOtp() {
    const otp   = getOtpValue();
    const email = otpState.pendingEmail;
    const msg   = document.getElementById('otp-msg');

    if (otp.length < 6) {
        msg.textContent = 'Please enter all 6 digits.';
        return;
    }

    msg.style.color = '#6b7280';
    msg.textContent = 'Verifying…';

    try {
        const res = await fetch('/auth/confirm-otp', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  getCsrfToken(),
                'Accept':        'application/json',
            },
            body: JSON.stringify({ email, otp }),
        });

        const data = await res.json();

        if (data.verified) {
            otpState.verifiedEmail = email;
            closeOtpModal();
            setEmailStatus('reg-email-status', 'valid', '✓ Email verified');
        } else {
            msg.style.color = '#e24b4a';
            msg.textContent = data.message || 'Invalid code. Please try again.';
            clearOtpDigits();
            getOtpDigits()[0]?.focus();
        }

    } catch {
        msg.style.color = '#e24b4a';
        msg.textContent = 'Network error. Please try again.';
    }
}

// ─── Register email field watcher ────────────────────────────────
// Replaces the old MX debounce. When the user finishes typing a valid
// email address and moves away (blur), we send the OTP automatically.

function validateEmail(input, statusId) {
    const email = input.value.trim();

    if (!email) {
        setEmailStatus(statusId, '', '');
        return;
    }

    if (!isValidEmailFormat(email)) {
        setEmailStatus(statusId, 'invalid', 'Please enter a valid email address.');
        return;
    }

    if (!requireOtpVerification) {
        setEmailStatus(statusId, 'valid', 'Email verification is disabled in local mode.');
        return;
    }

    // If the same email is already verified, show tick immediately
    if (otpState.verifiedEmail &&
        otpState.verifiedEmail.toLowerCase() === email.toLowerCase()) {
        setEmailStatus(statusId, 'valid', '✓ Email verified');
        return;
    }

    // Otherwise show a "Send code" prompt
    setEmailStatus(statusId, '', '');
    showSendCodePrompt(input, statusId, email);
}

// Renders a small "Send verification code" button next to the hint text
function showSendCodePrompt(input, statusId, email) {
    const hintId = getHintId(statusId);
    const hint   = document.getElementById(hintId);
    if (!hint) return;

    hint.innerHTML = '';
    hint.className = 'email-hint';

    const btn = document.createElement('button');
    btn.type      = 'button';
    btn.textContent = 'Send verification code';
    btn.style.cssText = [
        'background:none;border:none;padding:0;cursor:pointer',
        'color:#2563eb;font-size:13px;font-weight:600;margin:10px 0;',
    ].join(';');
    btn.addEventListener('click', () => sendOtp(email));

    hint.appendChild(btn);
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => toast.remove(), 5000);
}

// ─── Guard register form submission ──────────────────────────────
// Prevents the form from submitting if the email hasn't been OTP-verified.
document.addEventListener('DOMContentLoaded', () => {
    const regForm = document.getElementById('register-form') // update to your form's actual id
                 || document.querySelector('form[action*="register"]');

    // If the register email field was repopulated (old('email')) after a
    // failed validation, re-check it now so an already-verified email shows
    // the ✓ state immediately instead of the "send code" prompt.
    const regEmailInput = document.getElementById('reg-email');
    if (regEmailInput && regEmailInput.value.trim()) {
        validateEmail(regEmailInput, 'reg-email-status');
    }

    if (regForm) {
        regForm.addEventListener('submit', (e) => {
            if (!requireOtpVerification) {
                return;
            }

            const emailInput = regForm.querySelector('input[name="email"]');
            const email      = emailInput?.value.trim().toLowerCase();

            if (!otpState.verifiedEmail ||
                otpState.verifiedEmail.toLowerCase() !== email) {
                e.preventDefault();
                // Scroll to email field and show prompt
                emailInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setEmailStatus('reg-email-status', 'invalid',
                    'You must verify your email address before registering.');
                if (email && isValidEmailFormat(email)) {
                    showSendCodePrompt(emailInput, 'reg-email-status', email);
                }
            }
        });
    }
});

// ─── Password visibility toggle ───────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pw-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.target);
            if (!input) return;

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            input.classList.toggle('pw-revealed', isHidden);

            btn.querySelector('.pw-eye-open').style.display  = isHidden ? 'none' : '';
            btn.querySelector('.pw-eye-closed').style.display = isHidden ? '' : 'none';
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });
});