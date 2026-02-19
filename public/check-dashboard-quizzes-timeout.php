<?php
/**
 * Dashboard quizzes timeout probe (secure via key).
 *
 * Usage:
 *   /check-dashboard-quizzes-timeout.php?key=YOUR_SECRET
 * Optional:
 *   &tab=active|ended
 *   &limit=15
 *   &user_id=123
 */

$secret = 'QuizSnapMigrate2026Xp9k3m7'; // Set same as MIGRATION_RUN_KEY
if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid or missing key.');
}

@set_time_limit(180);
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function nowMs(): float
{
    return microtime(true) * 1000;
}

function formatMs(float $ms): string
{
    return number_format($ms, 2) . ' ms';
}

function printSection(string $title): void
{
    echo "\n" . $title . "\n";
    echo str_repeat('-', strlen($title)) . "\n";
}

function applyQuizScopeByUser(\Illuminate\Database\Eloquent\Builder $query, ?User $user): void
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
        if (!empty($classGroupIds)) {
            $q->whereIn('class_group_id', $classGroupIds);
        }
        if ($user && $user->id) {
            $q->orWhere('examiner_id', $user->id);
        }
        if (empty($classGroupIds) && (!$user || !$user->id)) {
            $q->whereRaw('1=0');
        }
    });
}

$tab = ($_GET['tab'] ?? 'active') === 'ended' ? 'ended' : 'active';
$limit = max(1, min(100, (int) ($_GET['limit'] ?? 15)));
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;

echo "QuizSnap dashboard/quizzes timeout probe\n";
echo "=======================================\n";
echo "Server time: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: " . app()->environment() . "\n";
echo "DB connection: " . config('database.default') . "\n";
echo "Tab: {$tab}\n";
echo "Limit: {$limit}\n";

try {
    $t0 = nowMs();
    DB::connection()->getPdo();
    $t1 = nowMs();
    printSection('Database ping');
    echo "Connection OK in " . formatMs($t1 - $t0) . "\n";

    $user = null;
    if ($userId) {
        $user = User::find($userId);
    }
    if (!$user) {
        $user = User::query()
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EXAMINER, User::DM_ROLE_COORDINATOR])
            ->orderBy('id')
            ->first();
    }

    printSection('Selected user context');
    if ($user) {
        echo "User ID: {$user->id}\n";
        echo "Role: " . ($user->role ?? 'n/a') . "\n";
        echo "Name: " . ($user->name ?? 'n/a') . "\n";
    } else {
        echo "No admin/examiner/coordinator user found. Query will use null user scope.\n";
    }

    $classGroupStart = nowMs();
    $classGroupIds = $user ? $user->classGroupIds() : [];
    $classGroupEnd = nowMs();
    echo "classGroupIds() count: " . count($classGroupIds) . "\n";
    echo "classGroupIds() time: " . formatMs($classGroupEnd - $classGroupStart) . "\n";

    DB::flushQueryLog();
    DB::enableQueryLog();

    $query = Quiz::query()
        ->with(['course', 'classGroup', 'academicClass'])
        ->withCount([
            'questions',
            'sessions as sessions_started_count' => fn ($q) => $q->whereNotNull('start_time'),
        ])
        ->orderByDesc('created_at');

    applyQuizScopeByUser($query, $user);
    if ($tab === 'ended') {
        $query->ended();
    } else {
        $query->active();
    }

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    printSection('Main query SQL (before paginate)');
    echo $sql . "\n";
    echo "Bindings: " . json_encode($bindings, JSON_UNESCAPED_UNICODE) . "\n";

    $q0 = nowMs();
    $paginator = $query->paginate($limit);
    $q1 = nowMs();

    $rowCheckStart = nowMs();
    $pending = 0;
    $started = 0;
    $active = 0;
    foreach ($paginator->items() as $quiz) {
        if (!$quiz->hasEnoughApprovedQuestions()) {
            $pending++;
        }
        if ($quiz->hasStarted()) {
            $started++;
        }
        if ($quiz->isActive()) {
            $active++;
        }
    }
    $rowCheckEnd = nowMs();

    $logs = DB::getQueryLog();
    DB::disableQueryLog();

    printSection('Timing results');
    echo "Paginate time: " . formatMs($q1 - $q0) . "\n";
    echo "Row status checks time: " . formatMs($rowCheckEnd - $rowCheckStart) . "\n";
    echo "Total measured time: " . formatMs(($q1 - $q0) + ($rowCheckEnd - $rowCheckStart)) . "\n";

    printSection('Result stats');
    echo "Total rows (paginator): {$paginator->total()}\n";
    echo "Rows in current page: " . count($paginator->items()) . "\n";
    echo "Pending rows: {$pending}\n";
    echo "Started rows: {$started}\n";
    echo "Active rows: {$active}\n";

    printSection('Query stats');
    echo "Captured SQL query count: " . count($logs) . "\n";

    $totalQueryMs = 0.0;
    foreach ($logs as $entry) {
        $totalQueryMs += (float) ($entry['time'] ?? 0);
    }
    echo "Total DB-reported SQL time: " . formatMs($totalQueryMs) . "\n";

    if (!empty($logs)) {
        echo "\nTop 5 slowest captured queries:\n";
        usort($logs, fn ($a, $b) => (float) ($b['time'] ?? 0) <=> (float) ($a['time'] ?? 0));
        $top = array_slice($logs, 0, 5);
        foreach ($top as $i => $entry) {
            $n = $i + 1;
            $t = formatMs((float) ($entry['time'] ?? 0));
            $sqlOneLine = preg_replace('/\s+/', ' ', (string) ($entry['query'] ?? ''));
            echo "  {$n}) {$t} | {$sqlOneLine}\n";
        }
    }

    if (config('database.default') === 'mysql') {
        printSection('Index check (quiz_sessions)');
        $idxRows = DB::select("SHOW INDEX FROM quiz_sessions");
        $indexNames = [];
        foreach ($idxRows as $row) {
            $name = (string) ($row->Key_name ?? '');
            if ($name !== '') {
                $indexNames[$name] = true;
            }
        }
        ksort($indexNames);
        foreach (array_keys($indexNames) as $name) {
            echo "  - {$name}\n";
        }
        echo "\nExpected after migration:\n";
        echo "  - quiz_sessions_quiz_id_ended_at_index\n";
        echo "  - quiz_sessions_quiz_id_start_time_index\n";
    }

    printSection('Interpretation guide');
    echo "- If query count is very high (> 20 for limit=15), there may still be N+1.\n";
    echo "- If query count is low but total SQL time is high, DB/indexing is the bottleneck.\n";
    echo "- If SQL time is low but total measured time is high, PHP/render/network timeout is likely.\n";
} catch (Throwable $e) {
    printSection('ERROR');
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

