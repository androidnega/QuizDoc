<?php

namespace App\Console\Commands;

use App\Services\OtpDiagnostics;
use Illuminate\Console\Command;

class DiagnoseOtpCommand extends Command
{
    protected $signature = 'quizsnap:otp-diagnose
        {index : Index as entered on login (e.g. BC/ITS/24/047)}
        {--code= : Optional 6-digit code (examiner or SMS) to test matching}';

    protected $description = 'Print OTP/student/hash diagnostics (read-only). Use on server when login says invalid code.';

    public function handle(): int
    {
        $index = (string) $this->argument('index');
        $codeOpt = $this->option('code');
        $rawCode = ($codeOpt !== null && $codeOpt !== '') ? (string) $codeOpt : null;

        $this->line(OtpDiagnostics::buildReport($index, $rawCode));

        return self::SUCCESS;
    }
}
