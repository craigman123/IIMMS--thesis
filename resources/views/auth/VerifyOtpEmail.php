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
 * Usage:
 *   Mail::to($email)->send(new VerifyOtpEmail($otp));
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

    /**
     * The 6-digit OTP code (plain text — hashed copy lives in the session).
     */
    public string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Verification Code',
        );
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
