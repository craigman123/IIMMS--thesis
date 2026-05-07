<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $context === 'login' ? 'Sign-In Verification Code' : 'Email Verification Code' }}</title>
<style>
    body        { margin:0; padding:0; background:#f4f4f5; font-family: 'Segoe UI', Arial, sans-serif; }
    .wrapper    { max-width:480px; margin:40px auto; background:#fff; border-radius:12px;
                  box-shadow:0 2px 12px rgba(0,0,0,.08); overflow:hidden; }
    .header     { background:#1a3a5c; padding:32px 40px; text-align:center; }
    .header h1  { margin:0; color:#fff; font-size:20px; letter-spacing:.5px; font-weight:600; }
    .header p   { margin:6px 0 0; color:#93c5fd; font-size:13px; letter-spacing:.3px; }
    .body       { padding:36px 40px; }
    .body p     { margin:0 0 16px; color:#374151; font-size:15px; line-height:1.6; }
    .otp-box    { margin:28px 0; text-align:center; }
    .otp-code   { display:inline-block; background:#f0f4ff; border:2px dashed #3b82f6;
                  border-radius:10px; padding:18px 40px; font-size:38px; font-weight:700;
                  letter-spacing:10px; color:#1d3a6e; font-family: 'Courier New', monospace; }
    .note       { background:#fef9ec; border-left:4px solid #f59e0b; padding:12px 16px;
                  border-radius:4px; margin-top:20px; }
    .note p     { margin:0; font-size:13px; color:#92400e; }
    .footer     { background:#f9fafb; padding:20px 40px; text-align:center;
                  border-top:1px solid #e5e7eb; }
    .footer p   { margin:0; font-size:12px; color:#9ca3af; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>
            @if ($context === 'login')
                Sign-In Verification
            @else
                Email Verification
            @endif
        </h1>
        <p>{{ config('app.name') }}</p>
    </div>
    <div class="body">
        <p>Hello,</p>

        @if ($context === 'login')
            <p>A sign-in attempt was made on your account. Use the code below to complete your login. It expires in <strong>10 minutes</strong>.</p>
        @else
            <p>Use the code below to verify your email address. Enter it in the registration form within <strong>10 minutes</strong>.</p>
        @endif

        <div class="otp-box">
            <span class="otp-code">{{ $otp }}</span>
        </div>

        <div class="note">
            <p>⚠️ Never share this code with anyone. Our staff will never ask for it.</p>
        </div>

        <p style="margin-top:24px;">
            @if ($context === 'login')
                If you did not attempt to sign in, your account may be at risk. Please change your password immediately.
            @else
                If you did not request this, you can safely ignore this email.
            @endif
        </p>
    </div>
    <div class="footer">
        <p>This code expires in 10 minutes &nbsp;·&nbsp; {{ config('app.name') }}</p>
    </div>
</div>
</body>
</html>