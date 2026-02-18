<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class EncryptExistingSettings extends Command
{
    protected $signature = 'settings:encrypt-existing';

    protected $description = 'Re-save sensitive settings so existing plaintext values are stored encrypted (one-time after enabling encryption)';

    public function handle(): int
    {
        $keys = [
            Setting::KEY_GEMINI_API,
            Setting::KEY_DEEPSEEK_API,
            Setting::KEY_OPENAI_API,
            Setting::KEY_CLOUDINARY_API_KEY,
            Setting::KEY_CLOUDINARY_API_SECRET,
            Setting::KEY_MAIL_PASSWORD,
        ];

        $count = 0;
        foreach ($keys as $key) {
            $value = Setting::getValue($key);
            if ($value !== null && $value !== '') {
                Setting::setValue($key, $value);
                $count++;
                $this->info("Encrypted: {$key}");
            }
        }

        $this->info("Done. {$count} setting(s) re-saved as encrypted.");
        return Command::SUCCESS;
    }
}
