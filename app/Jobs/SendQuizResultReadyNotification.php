<?php

namespace App\Jobs;

use App\Mail\QuizResultReadyNotification;
use App\Models\QuizSession;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SendQuizResultReadyNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $quizSessionId
    ) {}

    public function handle(): void
    {
        if (Setting::getValue(Setting::KEY_NOTIFY_RESULT_READY, '0') !== '1') {
            return;
        }
        $to = Setting::getValue(Setting::KEY_NOTIFY_RESULT_EMAIL);
        if (! $to || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $session = QuizSession::with(['quiz', 'result'])->find($this->quizSessionId);
        if (! $session || ! $session->result) {
            return;
        }

        $this->applyMailConfigFromSettings();

        Mail::to($to)->send(new QuizResultReadyNotification($session));
    }

    private function applyMailConfigFromSettings(): void
    {
        $mailer = Setting::getValue(Setting::KEY_MAIL_MAILER, config('mail.default'));
        $host = Setting::getValue(Setting::KEY_MAIL_HOST, config('mail.mailers.smtp.host'));
        $port = (int) Setting::getValue(Setting::KEY_MAIL_PORT, (string) (config('mail.mailers.smtp.port') ?? 587));
        $username = Setting::getValue(Setting::KEY_MAIL_USERNAME);
        $password = Setting::getValue(Setting::KEY_MAIL_PASSWORD);
        $encryption = Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, 'tls');
        $fromAddress = Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, config('mail.from.address'));
        $fromName = Setting::getValue(Setting::KEY_MAIL_FROM_NAME, config('mail.from.name'));

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress ?: 'noreply@quizsnap.local');
        Config::set('mail.from.name', $fromName ?: 'QuizSnap');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
    }
}
