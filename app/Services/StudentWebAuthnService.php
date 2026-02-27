<?php

namespace App\Services;

use App\Exceptions\PasskeyUnavailableException;
use App\Models\Student;
use App\Models\StudentPasskey;

class StudentWebAuthnService
{
    private ?object $webauthn = null;

    /**
     * Lazy-load WebAuthn. When $requestHost is provided, always use it as rp_id so it matches the browser origin
     * (avoids localhost vs 127.0.0.1 and subdomain mismatches on both local and production).
     */
    private function getWebAuthn(?string $requestHost = null): object
    {
        if (! class_exists('\\lbuchs\\WebAuthn\\WebAuthn', true)) {
            throw new PasskeyUnavailableException('Passkey sign-in is not available. Use your index number and code instead.');
        }
        $rpId = $requestHost !== null && $requestHost !== ''
            ? $requestHost
            : config('webauthn.rp_id');
        $allowedFormats = ['none', 'packed', 'apple'];
        $rpName = config('webauthn.rp_name');
        $useBase64 = config('webauthn.use_base64url', true);
        // Create a fresh instance per requestHost/rpId to avoid cross-origin mismatches.
        return new \lbuchs\WebAuthn\WebAuthn($rpName, $rpId, $allowedFormats, $useBase64);
    }

    /** Call only after getWebAuthn() so the package is loaded. */
    private function getByteBufferClass(): string
    {
        return '\\lbuchs\\WebAuthn\\Binary\\ByteBuffer';
    }

    /**
     * Generate options for navigator.credentials.create (registration).
     * Call when student is already logged in (session).
     * @param string|null $requestHost In local env, pass request host so rp_id matches the browser domain.
     */
    public function getRegisterOptions(Student $student, ?string $requestHost = null): object
    {
        $webauthn = $this->getWebAuthn($requestHost);
        $byteBuffer = $this->getByteBufferClass();
        $timeout = config('webauthn.timeout', 60);
        $excludeIds = $student->passkeys()->pluck('credential_id')->map(function ($id) use ($byteBuffer) {
            return $byteBuffer::fromBase64Url($id);
        })->all();

        $userId = pack('J', $student->id);
        $userName = $student->index_number;
        $userDisplayName = $student->display_name;

        return $webauthn->getCreateArgs(
            $userId,
            $userName,
            $userDisplayName,
            $timeout,
            'required',
            'preferred',
            false,
            $excludeIds
        );
    }

    /**
     * Convert options object to JSON-serializable array (ByteBuffer -> base64url string).
     */
    public function optionsToJsonable(object $options): array
    {
        $arr = json_decode(json_encode($options), true);
        if ($arr !== null) {
            return $arr;
        }
        return $this->objectToArray($options);
    }

    private function objectToArray(object $obj): array
    {
        $byteBuffer = $this->getByteBufferClass();
        $out = [];
        foreach ((array) $obj as $k => $v) {
            $k = trim((string) $k, "\0*");
            if ($v instanceof $byteBuffer) {
                $out[$k] = $v->getBase64Url();
            } elseif (is_object($v)) {
                $out[$k] = $this->objectToArray($v);
            } elseif (is_array($v)) {
                $out[$k] = array_map(function ($item) use ($byteBuffer) {
                    if ($item instanceof $byteBuffer) {
                        return $item->getBase64Url();
                    }
                    if (is_object($item)) {
                        return $this->objectToArray($item);
                    }
                    return $item;
                }, $v);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    /**
     * Process registration response from browser and store the passkey.
     * @param string|null $requestHost In local env, must match the host used in getRegisterOptions.
     */
    public function processRegister(Student $student, string $clientDataJSON, string $attestationObject, string $challengeBase64Url, ?string $deviceName = null, ?string $requestHost = null): StudentPasskey
    {
        $webauthn = $this->getWebAuthn($requestHost);
        $byteBuffer = $this->getByteBufferClass();
        $challenge = $byteBuffer::fromBase64Url($challengeBase64Url);
        $clientDataJSONBinary = $this->decodeClientDataJSON($clientDataJSON);
        $attestationObjectBinary = $this->base64UrlDecode($attestationObject);

        $data = $webauthn->processCreate(
            $clientDataJSONBinary,
            $attestationObjectBinary,
            $challenge,
            false,
            true,
            false,
            false
        );

        // Library may return credentialId as ByteBuffer or raw binary string; DB needs base64url (UTF-8 safe).
        $credentialIdRaw = $data->credentialId instanceof $byteBuffer
            ? $data->credentialId->getBinaryString()
            : (string) $data->credentialId;
        $credentialIdBase64 = $this->base64UrlEncode($credentialIdRaw);

        return StudentPasskey::create([
            'student_id' => $student->id,
            'credential_id' => $credentialIdBase64,
            'credential_public_key' => $data->credentialPublicKey,
            'counter' => $data->signatureCounter ?? 0,
            'device_name' => $deviceName,
        ]);
    }

    /**
     * Generate options for navigator.credentials.get (login).
     * @param string|null $requestHost In local env, pass request host so rp_id matches the browser domain.
     */
    public function getLoginOptions(?string $requestHost = null): object
    {
        $webauthn = $this->getWebAuthn($requestHost);
        $timeout = config('webauthn.timeout', 60);
        $args = $webauthn->getGetArgs([], $timeout, true, true, true, true, true, 'preferred');
        $challenge = $webauthn->getChallenge();
        if ($challenge !== null && method_exists($challenge, 'getBase64Url')) {
            $this->setStoredChallenge($challenge->getBase64Url());
        }
        return $args;
    }

    /**
     * Process login assertion: find student by credential id, verify signature, update counter, return student.
     * @param string|null $requestHost In local env, must match the host used when the passkey was registered.
     */
    public function processLogin(array $assertion, ?string $requestHost = null): ?Student
    {
        $credentialId = $assertion['rawId'] ?? $assertion['id'] ?? null;
        $clientDataJSON = $assertion['response']['clientDataJSON'] ?? null;
        $authenticatorData = $assertion['response']['authenticatorData'] ?? null;
        $signature = $assertion['response']['signature'] ?? null;

        if (! $credentialId || ! $clientDataJSON || ! $authenticatorData || ! $signature) {
            return null;
        }

        $credentialIdStr = is_string($credentialId) ? $credentialId : base64_encode($credentialId);
        $passkey = StudentPasskey::where('credential_id', $credentialIdStr)->first();
        if (! $passkey) {
            return null;
        }

        $challenge = $this->getStoredChallenge();
        if (! $challenge) {
            return null;
        }

        $webauthn = $this->getWebAuthn($requestHost);
        $byteBuffer = $this->getByteBufferClass();
        $clientDataJSONBinary = $this->base64UrlDecode($clientDataJSON);
        $authenticatorDataBinary = $this->base64UrlDecode($authenticatorData);
        $signatureBinary = $this->base64UrlDecode($signature);
        $challengeBuffer = $byteBuffer::fromBase64Url($challenge);

        try {
            $ok = $webauthn->processGet(
                $clientDataJSONBinary,
                $authenticatorDataBinary,
                $signatureBinary,
                $passkey->credential_public_key,
                $challengeBuffer,
                $passkey->counter,
                false,
                true
            );
        } catch (\Throwable $e) {
            return null;
        }

        if (! $ok) {
            return null;
        }

        $newCounter = $webauthn->getSignatureCounter();
        if ($newCounter !== null) {
            $passkey->update(['counter' => $newCounter]);
        }

        return $passkey->student;
    }

    public function getChallenge(?string $requestHost = null): ?object
    {
        return $this->getWebAuthn($requestHost)->getChallenge();
    }

    /**
     * Decode base64url to raw binary. Adds padding so decode is correct.
     */
    private function base64UrlDecode(string $input): string
    {
        $input = strtr($input, '-_', '+/');
        $pad = strlen($input) % 4;
        if ($pad) {
            $input .= str_repeat('=', 4 - $pad);
        }
        $bin = base64_decode($input, true);
        return $bin !== false ? $bin : '';
    }

    /**
     * Encode raw binary to base64url (ASCII, safe for UTF-8 DB columns).
     */
    private function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    /**
     * Decode clientDataJSON from request: base64url (browser) or raw JSON. Ensures valid UTF-8 for lbuchs.
     */
    private function decodeClientDataJSON(string $input): string
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return '';
        }
        if (str_starts_with($trimmed, '{')) {
            return $this->ensureUtf8($trimmed);
        }
        $decoded = $this->base64UrlDecode($trimmed);
        if ($decoded === '') {
            return '';
        }
        return $this->ensureUtf8($decoded);
    }

    /**
     * Strip invalid UTF-8 so json_decode in lbuchs does not throw "Malformed UTF-8".
     */
    private function ensureUtf8(string $str): string
    {
        if (mb_check_encoding($str, 'UTF-8')) {
            return $str;
        }
        if (function_exists('iconv')) {
            $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
            return $cleaned !== false ? $cleaned : $str;
        }
        return mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    }

    private function getStoredChallenge(): ?string
    {
        return session('webauthn_login_challenge');
    }

    public function setStoredChallenge(string $challenge): void
    {
        session(['webauthn_login_challenge' => $challenge]);
    }
}
