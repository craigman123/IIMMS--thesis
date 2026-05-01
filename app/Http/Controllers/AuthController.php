<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Mail\VerifyOtpEmail;
use App\Models\User;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN
    // ─────────────────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'badge_number' => ['required', 'string'],
            'password'     => ['required'],
        ]);

        $user = User::where('badge_number', strtoupper($request->badge_number))->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('admin.dashboard'); // change to staff later
        }

        return back()
            ->withInput($request->only('badge_number', '_form'))
            ->withErrors(['badge_number' => 'These credentials do not match our records.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTER
    // ─────────────────────────────────────────────────────────────────────────

    public function register(Request $request)
    {
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

        // ── OTP gate: the email must have been verified in this session ──────
        $verified = session('otp_verified_email');
        if (! $verified || strtolower($verified) !== strtolower($request->email)) {
            return back()
                ->withInput($request->only('first_name', 'last_name', 'badge_number', 'email', '_form'))
                ->withErrors(['email' => 'Please verify your email address before registering.']);
        }

        $user = User::create([
            'name'         => $request->first_name . ' ' . $request->last_name,
            'badge_number' => strtoupper($request->badge_number),
            'email'        => $request->email,
            'password'     => Hash::make($request->password),
        ]);

        // Clear the OTP session flag
        session()->forget(['otp_verified_email', 'otp_data']);

        Auth::login($user);

        return redirect('/dashboard');
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
    // OTP — SEND
    //
    // POST /auth/send-otp
    //
    // Generates a 6-digit OTP, stores it in the session with an expiry
    // timestamp, and sends it to the supplied email via SMTP (Laravel Mail).
    //
    // Rate-limited to 5 sends per minute per IP (see routes/web.php).
    // Returns JSON { sent: bool, message: string }
    // ─────────────────────────────────────────────────────────────────────────

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email:rfc'],
        ]);

        $email = strtolower(trim($request->email));

        // Prevent spamming: enforce a 60-second cooldown per email in the session
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

        // Generate a cryptographically random 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store in session: hashed for safety, expires in 10 minutes
        session([
            'otp_data' => [
                'email'      => $email,
                'hash'       => Hash::make($otp),
                'sent_at'    => time(),
                'expires_at' => time() + 600, // 10-minute window
                'attempts'   => 0,
            ],
        ]);

        // Send via SMTP using Laravel Mail
        try {
            Mail::to($email)->send(new VerifyOtpEmail($otp));
        } catch (\Throwable $e) {
            // Log the error but return a generic message to the client
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
    // OTP — CONFIRM
    //
    // POST /auth/confirm-otp
    //
    // Validates the submitted OTP against the session-stored hash.
    // Locks out after 5 wrong attempts (forces a new OTP request).
    // On success, marks the email as verified in the session.
    // Returns JSON { verified: bool, message: string }
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'digits:6'],
        ]);

        $email = strtolower(trim($request->email));
        $data  = session('otp_data');

        // ── Basic session checks ─────────────────────────────────────────────
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

        // ── Attempt limit ────────────────────────────────────────────────────
        if ($data['attempts'] >= 5) {
            session()->forget('otp_data');
            return response()->json([
                'verified' => false,
                'message'  => 'Too many incorrect attempts. Please request a new code.',
            ], 429);
        }

        // ── OTP check ────────────────────────────────────────────────────────
        if (! Hash::check($request->otp, $data['hash'])) {
            // Increment attempt counter
            $data['attempts']++;
            session(['otp_data' => $data]);

            $remaining = 5 - $data['attempts'];
            return response()->json([
                'verified' => false,
                'message'  => "Incorrect code. {$remaining} attempt(s) remaining.",
            ], 422);
        }

        // ── Success ──────────────────────────────────────────────────────────
        session()->forget('otp_data');
        session(['otp_verified_email' => $email]);

        return response()->json([
            'verified' => true,
            'message'  => 'Email verified successfully.',
        ]);
    }
}