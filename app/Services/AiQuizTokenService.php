<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Manages AI quiz generation tokens for examiners.
 * Tokens refill after a cooldown period when exhausted.
 */
class AiQuizTokenService
{
    /** Default cooldown hours when tokens are exhausted. */
    public const DEFAULT_COOLDOWN_HOURS = 24;

    public function getCooldownHours(): int
    {
        $val = Setting::getValue(Setting::KEY_AI_QUIZ_COOLDOWN_HOURS, (string) self::DEFAULT_COOLDOWN_HOURS);
        return max(1, min(168, (int) $val)); // 1–168 hours
    }

    /**
     * Whether the user can use AI quiz generation (Super Admin bypasses; examiners need tokens).
     */
    public function canUse(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        $isCoordinator = $user->role === User::DM_ROLE_COORDINATOR;
        $isExaminer = $user->isExaminer();
        if (! $isExaminer && ! $isCoordinator) {
            return false;
        }
        return $this->getRemaining($user) > 0;
    }

    /**
     * Get token status for display.
     *
     * @return array{remaining: int, allocation: int, used: int, reset_at: ?\Carbon\Carbon, can_use: bool, message: ?string}
     */
    public function getStatus(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return [
                'remaining' => 999,
                'allocation' => 999,
                'used' => 0,
                'reset_at' => null,
                'can_use' => true,
                'message' => null,
            ];
        }
        $isCoordinator = $user->role === User::DM_ROLE_COORDINATOR;
        $isExaminer = $user->isExaminer();
        if (! $isExaminer && ! $isCoordinator) {
            return [
                'remaining' => 0,
                'allocation' => 0,
                'used' => 0,
                'reset_at' => null,
                'can_use' => false,
                'message' => null,
            ];
        }

        $this->maybeReset($user);

        // Default allocation: examiners 10, coordinators 3 (per period, typically per day).
        $defaultAllocation = $isCoordinator ? 3 : 10;
        $allocation = (int) ($user->ai_quiz_tokens_allocation ?? $defaultAllocation);
        $used = (int) ($user->ai_quiz_tokens_used ?? 0);
        $remaining = max(0, $allocation - $used);
        $resetAt = $user->ai_quiz_tokens_reset_at ? \Carbon\Carbon::parse($user->ai_quiz_tokens_reset_at) : null;

        $canUse = $remaining > 0;
        $message = null;
        if (!$canUse && $resetAt) {
            $message = 'You have no AI quiz tokens left. Tokens refill at ' . $resetAt->format('M j, g:i A') . ' (' . $resetAt->diffForHumans() . ').';
        } elseif (!$canUse) {
            $message = 'You have no AI quiz tokens left. Contact your administrator to request more.';
        }

        return [
            'remaining' => $remaining,
            'allocation' => $allocation,
            'used' => $used,
            'reset_at' => $resetAt,
            'can_use' => $canUse,
            'message' => $message,
        ];
    }

    /**
     * Consume one token. Call only after canUse() returns true.
     * Returns false if token could not be consumed (race condition).
     */
    public function consume(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $isCoordinator = $user->role === User::DM_ROLE_COORDINATOR;
        $isExaminer = $user->isExaminer();
        if (! $isExaminer && ! $isCoordinator) {
            return false;
        }

        $this->maybeReset($user);

        $affected = DB::table('users')
            ->where('id', $user->id)
            ->whereRaw('(ai_quiz_tokens_used < ai_quiz_tokens_allocation)')
            ->update([
                'ai_quiz_tokens_used' => DB::raw('ai_quiz_tokens_used + 1'),
                'updated_at' => now(),
            ]);

        if ($affected > 0) {
            $user->refresh();
            $used = (int) ($user->ai_quiz_tokens_used ?? 0);
            $allocation = (int) ($user->ai_quiz_tokens_allocation ?? 10);
            if ($used >= $allocation) {
                $cooldownHours = $this->getCooldownHours();
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'ai_quiz_tokens_reset_at' => now()->addHours($cooldownHours),
                        'updated_at' => now(),
                    ]);
            }
            return true;
        }

        return false;
    }

    private function maybeReset(User $user): void
    {
        $resetAt = $user->ai_quiz_tokens_reset_at;
        if (!$resetAt) {
            return;
        }
        $resetAt = \Carbon\Carbon::parse($resetAt);
        if (now()->gte($resetAt)) {
            $user->update([
                'ai_quiz_tokens_used' => 0,
                'ai_quiz_tokens_reset_at' => null,
            ]);
        }
    }

    public function getRemaining(User $user): int
    {
        return $this->getStatus($user)['remaining'];
    }
}
