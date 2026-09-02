<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyOtpEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN — Show
    // ─────────────────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN — Submit
    //
    // Validates credentials. On success, does NOT log the user in immediately.
    // Instead it stores the user_id in the session as a "pending" login and
    // sends an OTP to the user's registered email, then redirects to the
    // OTP verification page.
    // ─────────────────────────────────────────────────────────────────────────

    public function login(Request $request)
    {
        $request->validate([
            'badge_number' => ['required', 'string'],
            'password'     => ['required'],
        ]);

        $user = User::where('badge_number', strtoupper($request->badge_number))->first();

        // Wrong credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()
                ->withInput($request->only('badge_number', '_form'))
                ->withErrors(['badge_number' => 'These credentials do not match our records.']);
        }

        // ── Credentials are valid — send OTP before completing the login ─────
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store pending login state in session
        session([
            'login_pending' => [
                'user_id'    => $user->id,
                'remember'   => $request->boolean('remember'),
                'otp_hash'   => Hash::make($otp),
                'sent_at'    => time(),
                'expires_at' => time() + 600, // 10 minutes
                'attempts'   => 0,
            ],
        ]);

        // ── Account must be approved before login proceeds ────────────────────
        if ($user->status !== 'approved') {
            return back()
                ->withInput($request->only('badge_number', '_form'))
                ->withErrors(['badge_number' => 'Your account is pending administrator approval.']);
        }

        // Send OTP to the user's registered email
        try {
            Mail::to($user->email)->send(new VerifyOtpEmail($otp, 'login'));
        } catch (\Throwable $e) {
            logger()->error('Login OTP mail failure: ' . $e->getMessage());
            session()->forget('login_pending');
            return back()
                ->withInput($request->only('badge_number', '_form'))
                ->withErrors(['badge_number' => 'Could not send verification email. Please try again.']);
        }

        $masked = $this->maskEmail($user->email);

        return redirect()->route('login.otp')
            ->with('otp_email_hint', $masked);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN OTP — Show page
    // ─────────────────────────────────────────────────────────────────────────

    public function showLoginOtp()
    {
        // Guard: must have a pending login in session
        if (! session('login_pending')) {
            return redirect()->route('login');
        }

        return view('emails.verify-login-otp');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN OTP — Confirm
    //
    // POST /login/verify-otp
    // Validates the OTP. On success, completes the Auth::login() and
    // redirects to the dashboard.
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmLoginOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $pending = session('login_pending');

        if (! $pending) {
            return redirect()->route('login')
                ->withErrors(['otp' => 'Session expired. Please sign in again.']);
        }

        // Expired
        if (time() > $pending['expires_at']) {
            session()->forget('login_pending');
            return redirect()->route('login')
                ->withErrors(['otp' => 'Verification code expired. Please sign in again.']);
        }

        // Too many attempts
        if ($pending['attempts'] >= 5) {
            session()->forget('login_pending');
            return redirect()->route('login')
                ->withErrors(['otp' => 'Too many incorrect attempts. Please sign in again.']);
        }

        // Wrong OTP
        if (! Hash::check($request->otp, $pending['otp_hash'])) {
            $pending['attempts']++;
            session(['login_pending' => $pending]);
            $remaining = 5 - $pending['attempts'];

            return back()->withErrors([
                'otp' => "Incorrect code. {$remaining} attempt(s) remaining.",
            ]);
        }

        // ── OTP correct — complete the login ─────────────────────────────────
        $user = User::find($pending['user_id']);

        if (! $user) {
            session()->forget('login_pending');
            return redirect()->route('login')
                ->withErrors(['badge_number' => 'Account not found. Please sign in again.']);
        }

        session()->forget('login_pending');

        Auth::login($user, $pending['remember']);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN OTP — Resend
    //
    // POST /login/resend-otp
    // Lets the user request a fresh OTP without going back to the login form.
    // Enforces a 60-second cooldown.
    // ─────────────────────────────────────────────────────────────────────────

    public function resendLoginOtp(Request $request)
    {
        $pending = session('login_pending');

        if (! $pending) {
            return response()->json(['sent' => false, 'message' => 'Session expired. Please sign in again.'], 422);
        }

        // Cooldown check
        if ((time() - $pending['sent_at']) < 60) {
            $wait = 60 - (time() - $pending['sent_at']);
            return response()->json(['sent' => false, 'message' => "Please wait {$wait} seconds before requesting another code."], 429);
        }

        $user = User::find($pending['user_id']);
        if (! $user) {
            return response()->json(['sent' => false, 'message' => 'Account not found.'], 422);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $pending['otp_hash']   = Hash::make($otp);
        $pending['sent_at']    = time();
        $pending['expires_at'] = time() + 600;
        $pending['attempts']   = 0;
        session(['login_pending' => $pending]);

        try {
            Mail::to($user->email)->send(new VerifyOtpEmail($otp, 'login'));
        } catch (\Throwable $e) {
            logger()->error('Login OTP resend failure: ' . $e->getMessage());
            return response()->json(['sent' => false, 'message' => 'Failed to send email. Please try again.'], 500);
        }

        return response()->json(['sent' => true, 'message' => 'A new verification code has been sent.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTER
    // ─────────────────────────────────────────────────────────────────────────

    public function register(Request $request)
    {
        $requiresOtpVerification = ! app()->environment('local');

        $validator = Validator::make($request->all(), [
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'badge_number' => ['required', 'string', 'max:20', 'unique:users,badge_number'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'terms'        => ['accepted'],
        ], [
            'terms.accepted'        => 'You must agree to the Terms of Use.',
            'email.unique'          => 'An account with this email already exists.',
            'badge_number.unique'   => 'This badge number is already registered.',
            'badge_number.required' => 'Your authorized badge number is required.',
            'password.confirmed'    => 'The passwords do not match.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput($request->only('first_name', 'last_name', 'badge_number', 'email', '_form'))
                ->withErrors($validator);
        }

        // OTP gate
        $verified = session('otp_verified_email');
        if (
            $requiresOtpVerification &&
            (! $verified || strtolower($verified) !== strtolower($request->email))
        ) {
            return back()
                ->withInput($request->only('first_name', 'last_name', 'badge_number', 'email', '_form'))
                ->withErrors(['email' => 'Please verify your email address before registering.']);
        }

        $user = User::create([
            'name'         => $request->first_name . ' ' . $request->last_name,
            'badge_number' => strtoupper($request->badge_number),
            'email'        => $request->email,
            'password'     => Hash::make($request->password),
            // status defaults to 'pending' via migration
        ]);

        session()->forget(['otp_verified_email', 'otp_data']);

        return redirect()->route('login')
            ->with('status', 'Account created. An administrator must approve your account before you can sign in.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OTP — SEND (registration)
    // ─────────────────────────────────────────────────────────────────────────

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email:rfc'],
        ]);

        $email = strtolower(trim($request->email));

        // An email can only be tied to one registration. Reject early so the
        // user isn't sent (and doesn't have to enter) a code for an address
        // that will fail the uniqueness check at final submit anyway.
        if (User::where('email', $email)->exists()) {
            return response()->json([
                'sent'    => false,
                'message' => 'An account with this email already exists. Please sign in instead.',
            ], 422);
        }

        $existing = session('otp_data');
        if (
            $existing &&
            isset($existing['email'], $existing['sent_at']) &&
            $existing['email'] === $email &&
            (time() - $existing['sent_at']) < 60
        ) {
            $wait = 60 - (time() - $existing['sent_at']);
            return response()->json([
                'sent'    => false,
                'message' => "Please wait {$wait} seconds before requesting another code.",
            ], 429);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session([
            'otp_data' => [
                'email'      => $email,
                'hash'       => Hash::make($otp),
                'sent_at'    => time(),
                'expires_at' => time() + 600,
                'attempts'   => 0,
            ],
        ]);

        try {
            Mail::to($email)->send(new VerifyOtpEmail($otp));
        } catch (\Throwable $e) {
            logger()->error('OTP mail failure: ' . $e->getMessage());
            return response()->json([
                'sent'    => false,
                'message' => 'We could not send an email to that address. Please check the address and try again.',
            ], 500);
        }

        return response()->json([
            'sent'    => true,
            'message' => 'A 6-digit verification code has been sent to ' . $email . '. It expires in 10 minutes.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OTP — CONFIRM (registration)
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'digits:6'],
        ]);

        $email = strtolower(trim($request->email));
        $data  = session('otp_data');

        if (! $data || ($data['email'] ?? '') !== $email) {
            return response()->json([
                'verified' => false,
                'message'  => 'No pending verification for this email. Please request a new code.',
            ], 422);
        }

        if (time() > $data['expires_at']) {
            session()->forget('otp_data');
            return response()->json([
                'verified' => false,
                'message'  => 'Your verification code has expired. Please request a new one.',
            ], 422);
        }

        if ($data['attempts'] >= 5) {
            session()->forget('otp_data');
            return response()->json([
                'verified' => false,
                'message'  => 'Too many incorrect attempts. Please request a new code.',
            ], 429);
        }

        if (! Hash::check($request->otp, $data['hash'])) {
            $data['attempts']++;
            session(['otp_data' => $data]);
            $remaining = 5 - $data['attempts'];
            return response()->json([
                'verified' => false,
                'message'  => "Incorrect code. {$remaining} attempt(s) remaining.",
            ], 422);
        }

        session()->forget('otp_data');
        session(['otp_verified_email' => $email]);

        return response()->json([
            'verified' => true,
            'message'  => 'Email verified successfully.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────────────────

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(2, strlen($local)));
        $masked  = $visible . str_repeat('*', max(0, strlen($local) - 2));
        return $masked . '@' . $domain;
    }
}