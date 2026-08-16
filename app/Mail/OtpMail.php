<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    // متحول عام لحفظ الرمز وتمريره لصفحة العرض (HTML)
    public $otpCode;

    /**
     * Create a new message instance.
     */
    public function __construct($otpCode)
    {
        $this->otpCode = $otpCode;
    }

    /**
     * Get the message envelope (عنوان الإيميل).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.notifications.otp_email_subject'),
        );
    }

    /**
     * Get the message content definition (ملف التصميم الذي سيُعرض).
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
