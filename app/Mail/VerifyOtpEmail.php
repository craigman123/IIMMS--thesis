<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: OTP Verification Email
 *
 * Shared by both the login flow and the registration flow.
 *
 * Usage (login):
 *   Mail::to($user->email)->send(new VerifyOtpEmail($otp, 'login'));
 *
 * Usage (registration):
 *   Mail::to($email)->send(new VerifyOtpEmail($otp, 'register'));
 *
 * Configure SMTP in your .env:
 *   MAIL_MAILER=smtp
 *   MAIL_HOST=smtp.example.com
 *   MAIL_PORT=587
 *   MAIL_USERNAME=your@email.com
 *   MAIL_PASSWORD=yourpassword
 *   MAIL_ENCRYPTION=tls
 *   MAIL_FROM_ADDRESS=no-reply@yourdomain.com
 *   MAIL_FROM_NAME="Your App Name"
 */
class VerifyOtpEmail extends Mailable
{
    use Queueable, SerializesModels;

    /** The 6-digit OTP code (plain text — hashed copy lives in the session). */
    public string $otp;

    /** 'login' | 'register' — controls the email subject and body copy. */
    public string $context;

    public function __construct(string $otp, string $context = 'register')
    {
        $this->otp     = $otp;
        $this->context = $context;
    }

    public function envelope(): Envelope
    {
        $subject = $this->context === 'login'
            ? 'Your Sign-In Verification Code'
            : 'Your Email Verification Code';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-otp',   // resources/views/emails/verify-otp.blade.php
        );
    }

    public function attachments(): array
    {
        return [];
    }
}