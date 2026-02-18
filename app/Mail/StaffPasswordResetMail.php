<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $resetUrl
    ) {}

    public function envelope(): Envelope
    {
        $appName = Setting::getValue(Setting::KEY_APP_NAME, config('app.name'));
        return new Envelope(
            subject: "[{$appName}] Reset your password",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff-password-reset',
        );
    }
}
