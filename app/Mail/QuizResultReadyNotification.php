<?php

namespace App\Mail;

use App\Models\QuizSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuizResultReadyNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuizSession $session
    ) {}

    public function envelope(): Envelope
    {
        $quizTitle = $this->session->quiz?->title ?? 'Quiz';
        $appName = \App\Models\Setting::getValue(\App\Models\Setting::KEY_APP_NAME, config('app.name'));
        return new Envelope(
            subject: "[{$appName}] Quiz result ready: {$quizTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quiz-result-ready',
        );
    }
}
