<?php

namespace App\Http\Controllers\Student;

use App\Exceptions\PasskeyUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentWebAuthnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WebAuthn (passkey / fingerprint / Face ID) for students only.
 * Not used for staff or admin.
 */
class StudentWebAuthnController extends Controller
{
    public function __construct(
        private StudentWebAuthnService $webauthn
    ) {}

    /**
     * Get options for passkey registration. Student must be logged in (session).
     */
    public function registerOptions(Request $request): JsonResponse
    {
        $studentId = session('student_id');
        if (! $studentId) {
            return response()->json(['success' => false, 'message' => 'You must be logged in to add a passkey.'], 403);
        }

        $student = Student::find($studentId);
        if (! $student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        try {
            $requestHost = $request->getHost();
            $options = $this->webauthn->getRegisterOptions($student, $requestHost);
            $jsonable = $this->webauthn->optionsToJsonable($options);
            // Ensure challenge is in the response (lbuchs may not include it in the serialized object)
            $challenge = $this->webauthn->getChallenge($requestHost);
            if ($challenge !== null && method_exists($challenge, 'getBase64Url')) {
                $jsonable['publicKey'] = $jsonable['publicKey'] ?? [];
                $jsonable['publicKey']['challenge'] = $challenge->getBase64Url();
            }
            return response()->json(['success' => true, 'options' => $jsonable]);
        } catch (PasskeyUnavailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::warning('Student WebAuthn register options failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not prepare passkey registration.'], 500);
        }
    }

    /**
     * Process passkey registration (create credential) and store it.
     */
    public function register(Request $request): JsonResponse
    {
        $studentId = session('student_id');
        if (! $studentId) {
            return response()->json(['success' => false, 'message' => 'You must be logged in to add a passkey.'], 403);
        }

        $student = Student::find($studentId);
        if (! $student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        $request->validate([
            'clientDataJSON' => 'required|string',
            'attestationObject' => 'required|string',
            'challenge' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        try {
            $this->webauthn->processRegister(
                $student,
                $request->clientDataJSON,
                $request->attestationObject,
                $request->challenge,
                $request->device_name,
                $request->getHost()
            );
            return response()
                ->json([
                    'success' => true,
                    'message' => 'Passkey added. You can sign in with fingerprint or Face ID on this device next time.',
                ])
                // Hint for the login page: only show passkey button on this device when a passkey has been registered.
                ->cookie(
                    'quizsnap_has_passkey',
                    '1',
                    60 * 24 * 365,
                    '/',
                    null,
                    config('session.secure', false),
                    true,
                    false,
                    'Lax'
                );
        } catch (PasskeyUnavailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'not found') || str_contains($e->getMessage(), 'Class')) {
                return response()->json(['success' => false, 'message' => 'Passkey sign-in is not available. Use your index number and code instead.']);
            }
            $rpId = config('webauthn.rp_id');
            Log::warning('Student WebAuthn register error', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'rp_id' => $rpId,
                'has_challenge' => $request->filled('challenge'),
                'challenge_length' => strlen((string) $request->challenge),
                'client_data_length' => strlen((string) $request->clientDataJSON),
                'attestation_length' => strlen((string) $request->attestationObject),
            ]);
            $message = 'Could not add passkey. Try again or skip.';
            $payload = ['success' => false, 'message' => $message];
            if (config('app.debug')) {
                $payload['debug_error'] = $this->ensureUtf8(get_class($e) . ': ' . $e->getMessage());
                $payload['debug_rp_id'] = config('webauthn.rp_id');
                $payload['debug_tip'] = 'If debug_rp_id is 127.0.0.1, open this site at http://127.0.0.1 (not localhost). Or set WEBAUTHN_RP_ID=localhost in .env and use http://localhost.';
                $payload['message'] = $message . ' (See debug_error below)';
            }
            return response()->json($payload);
        }
    }

    /**
     * Get options for passkey login (discoverable credential).
     */
    public function loginOptions(Request $request): JsonResponse
    {
        if (session('student_id')) {
            return response()->json(['success' => false, 'message' => 'You are already logged in.'], 422);
        }

        try {
            $requestHost = $request->getHost();
            $options = $this->webauthn->getLoginOptions($requestHost);
            $jsonable = $this->webauthn->optionsToJsonable($options);
            return response()->json(['success' => true, 'options' => $jsonable]);
        } catch (PasskeyUnavailableException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'not found') || str_contains($e->getMessage(), 'Class')) {
                return response()->json(['success' => false, 'message' => 'Passkey sign-in is not available. Use your index number and code instead.']);
            }
            Log::warning('Student WebAuthn login options failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not start passkey sign-in.'], 500);
        }
    }

    /**
     * Process passkey assertion and log the student in (session).
     */
    public function login(Request $request): JsonResponse
    {
        if (session('student_id')) {
            return response()->json(['success' => false, 'message' => 'You are already logged in.'], 422);
        }

        $request->validate([
            'assertion' => 'required|array',
            'assertion.id' => 'required|string',
            'assertion.rawId' => 'required|string',
            'assertion.response' => 'required|array',
            'assertion.response.clientDataJSON' => 'required|string',
            'assertion.response.authenticatorData' => 'required|string',
            'assertion.response.signature' => 'required|string',
        ]);

        $assertion = $request->assertion;
        $student = $this->webauthn->processLogin($assertion, $request->getHost());

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'No passkey set up for this device yet. Sign in with your index number and code first; after signing in you can add fingerprint or Face ID for next time.',
            ], 422);
        }

        session([
            'student_id' => $student->id,
            'student_index' => $student->index_number,
        ]);

        $redirect = $student->level === null || $student->level === ''
            ? route('student.select-level')
            : route('dashboard');

        return response()->json([
            'success' => true,
            'redirect' => $redirect,
        ]);
    }

    /** Strip invalid UTF-8 so JSON responses never throw "Malformed UTF-8". */
    private function ensureUtf8(string $str): string
    {
        if (mb_check_encoding($str, 'UTF-8')) {
            return $str;
        }
        if (function_exists('iconv')) {
            $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
            return $cleaned !== false ? $cleaned : $str;
        }
        return (string) mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    }
}
