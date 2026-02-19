<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckDashboardQuizzesTimeoutController extends Controller
{
    private const DEFAULT_SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    /**
     * Dashboard quizzes timeout probe. Use: /check-dashboard-quizzes-timeout?key=YOUR_SECRET
     */
    public function __invoke(Request $request): Response
    {
        $secret = env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET);
        if ($request->query('key') !== $secret) {
            return response('Invalid or missing key.', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        @set_time_limit(180);

        $tab = ($request->query('tab') === 'ended') ? 'ended' : 'active';
        $limit = max(1, min(100, (int) $request->query('limit', 15)));
        $userId = $request->filled('user_id') ? (int) $request->query('user_id') : null;

        $out = "QuizSnap dashboard/quizzes timeout probe\n";
        $out .= "=======================================\n";
        $out .= "Server time: " . date('Y-m-d H:i:s') . "\n";
        $out .= "Environment: " . app()->environment() . "\n";
        $out .= "DB connection: " . config('database.default') . "\n";
        $out .= "Tab: {$tab}\n";
        $out .= "Limit: {$limit}\n";

        try {
            $t0 = $this->nowMs();
            DB::connection()->getPdo();
            $t1 = $this->nowMs();
            $out .= $this->section('Database ping');
            $out .= "Connection OK in " . $this->formatMs($t1 - $t0) . "\n";

            $user = $userId ? User::find($userId) : null;
            if (! $user) {
                $user = User::query()
                    ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER, User::DM_ROLE_COORDINATOR])
                    ->orderBy('id')
                    ->first();
            }

            $out .= $this->section('Selected user context');
            if ($user) {
                $out .= "User ID: {$user->id}\n";
                $out .= "Role: " . ($user->role ?? 'n/a') . "\n";
                $out .= "Name: " . ($user->name ?? 'n/a') . "\n";
            } else {
                $out .= "No admin/examiner/coordinator user found. Query will use null user scope.\n";
            }

            $cg0 = $this->nowMs();
            $classGroupIds = $user ? $user->classGroupIds() : [];
            $cg1 = $this->nowMs();
            $out .= "classGroupIds() count: " . count($classGroupIds) . "\n";
            $out .= "classGroupIds() time: " . $this->formatMs($cg1 - $cg0) . "\n";

            DB::flushQueryLog();
            DB::enableQueryLog();

            $query = Quiz::query()
                ->with(['course', 'classGroup', 'academicClass'])
                ->withCount([
                    'questions',
                    'sessions as sessions_started_count' => fn ($q) => $q->whereNotNull('start_time'),
                ])
                ->orderByDesc('created_at');

            $this->applyQuizScopeByUser($query, $user);
            if ($tab === 'ended') {
                $query->ended();
            } else {
                $query->active();
            }

            $out .= $this->section('Main query SQL (before paginate)');
            $out .= $query->toSql() . "\n";
            $out .= "Bindings: " . json_encode($query->getBindings(), JSON_UNESCAPED_UNICODE) . "\n";

            $q0 = $this->nowMs();
            $paginator = $query->paginate($limit);
            $q1 = $this->nowMs();

            $row0 = $this->nowMs();
            $pending = $started = $active = 0;
            foreach ($paginator->items() as $quiz) {
                if (! $quiz->hasEnoughApprovedQuestions()) {
                    $pending++;
                }
                if ($quiz->hasStarted()) {
                    $started++;
                }
                if ($quiz->isActive()) {
                    $active++;
                }
            }
            $row1 = $this->nowMs();

            $logs = DB::getQueryLog();
            DB::disableQueryLog();

            $out .= $this->section('Timing results');
            $out .= "Paginate time: " . $this->formatMs($q1 - $q0) . "\n";
            $out .= "Row status checks time: " . $this->formatMs($row1 - $row0) . "\n";
            $out .= "Total measured time: " . $this->formatMs(($q1 - $q0) + ($row1 - $row0)) . "\n";

            $out .= $this->section('Result stats');
            $out .= "Total rows (paginator): {$paginator->total()}\n";
            $out .= "Rows in current page: " . count($paginator->items()) . "\n";
            $out .= "Pending rows: {$pending}\n";
            $out .= "Started rows: {$started}\n";
            $out .= "Active rows: {$active}\n";

            $out .= $this->section('Query stats');
            $out .= "Captured SQL query count: " . count($logs) . "\n";
            $totalQueryMs = array_sum(array_map(fn ($e) => (float) ($e['time'] ?? 0), $logs));
            $out .= "Total DB-reported SQL time: " . $this->formatMs($totalQueryMs) . "\n";

            if (! empty($logs)) {
                $out .= "\nTop 5 slowest captured queries:\n";
                usort($logs, fn ($a, $b) => (float) ($b['time'] ?? 0) <=> (float) ($a['time'] ?? 0));
                foreach (array_slice($logs, 0, 5) as $i => $entry) {
                    $t = $this->formatMs((float) ($entry['time'] ?? 0));
                    $sql = preg_replace('/\s+/', ' ', (string) ($entry['query'] ?? ''));
                    $out .= "  " . ($i + 1) . ") {$t} | {$sql}\n";
                }
            }

            if (config('database.default') === 'mysql') {
                $out .= $this->section('Index check (quiz_sessions)');
                $idxRows = DB::select('SHOW INDEX FROM quiz_sessions');
                $indexNames = [];
                foreach ($idxRows as $row) {
                    $name = (string) ($row->Key_name ?? '');
                    if ($name !== '') {
                        $indexNames[$name] = true;
                    }
                }
                ksort($indexNames);
                foreach (array_keys($indexNames) as $name) {
                    $out .= "  - {$name}\n";
                }
                $out .= "\nExpected after migration:\n";
                $out .= "  - quiz_sessions_quiz_id_ended_at_index\n";
                $out .= "  - quiz_sessions_quiz_id_start_time_index\n";
            }

            $out .= $this->section('Interpretation guide');
            $out .= "- If query count is very high (> 20 for limit=15), there may still be N+1.\n";
            $out .= "- If query count is low but total SQL time is high, DB/indexing is the bottleneck.\n";
            $out .= "- If SQL time is low but total measured time is high, PHP/render/network timeout is likely.\n";
        } catch (\Throwable $e) {
            $out .= $this->section('ERROR');
            $out .= $e->getMessage() . "\n";
            $out .= $e->getTraceAsString() . "\n";
        }

        return response($out, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    private function nowMs(): float
    {
        return microtime(true) * 1000;
    }

    private function formatMs(float $ms): string
    {
        return number_format($ms, 2) . ' ms';
    }

    private function section(string $title): string
    {
        return "\n" . $title . "\n" . str_repeat('-', strlen($title)) . "\n";
    }

    private function applyQuizScopeByUser($query, ?User $user): void
    {
        $classGroupIds = $user ? $user->classGroupIds() : [];

        if ($user && $user->isSuperAdmin()) {
            return;
        }
        if ($user && $user->isExaminer()) {
            $query->where('examiner_id', $user->id);
            return;
        }
        $query->where(function ($q) use ($classGroupIds, $user) {
            if (! empty($classGroupIds)) {
                $q->whereIn('class_group_id', $classGroupIds);
            }
            if ($user && $user->id) {
                $q->orWhere('examiner_id', $user->id);
            }
            if (empty($classGroupIds) && (! $user || ! $user->id)) {
                $q->whereRaw('1=0');
            }
        });
    }
}
