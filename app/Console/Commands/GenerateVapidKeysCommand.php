<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'exam:generate-vapid';
    protected $description = 'Generate VAPID keys for web push (exam reminders). Add the output to .env as VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY.';

    public function handle(): int
    {
        $keyPair = $this->generateVapidKeys();
        if (!$keyPair) {
            $this->error('Could not generate keys. Ensure OpenSSL is enabled.');
            return self::FAILURE;
        }
        $this->line('Add these to your .env file:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY=' . $keyPair['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keyPair['privateKey']);
        $this->newLine();
        $this->info('Then run: php artisan config:clear');
        return self::SUCCESS;
    }

    private function generateVapidKeys(): ?array
    {
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ];
        $res = @openssl_pkey_new($config);
        if (!$res) {
            return null;
        }
        $details = openssl_pkey_get_details($res);
        if (!$details || !isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            return null;
        }
        $publicKeyBin = "\x04" . $details['ec']['x'] . $details['ec']['y'];
        $publicKey = $this->base64UrlEncode($publicKeyBin);
        $privateKey = $this->base64UrlEncode($details['ec']['d']);
        return ['publicKey' => $publicKey, 'privateKey' => $privateKey];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
