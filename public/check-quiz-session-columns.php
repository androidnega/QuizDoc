<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain');

echo "Checking quiz_sessions table structure...\n\n";

try {
    $columns = Schema::getColumnListing('quiz_sessions');
    
    $requiredColumns = [
        'id',
        'quiz_id',
        'student_index',
        'ip_address',
        'start_time',
        'ended_at',
        'pre_face_image',
        'post_face_image',
        'assigned_question_ids',
        'assigned_correct_answers',
        'shuffled_question_options',
        'session_token',
        'created_at',
        'updated_at',
    ];
    
    echo "Existing columns:\n";
    foreach ($columns as $col) {
        echo "  ✓ $col\n";
    }
    
    echo "\nRequired columns check:\n";
    $missing = [];
    foreach ($requiredColumns as $req) {
        if (in_array($req, $columns)) {
            echo "  ✓ $req - EXISTS\n";
        } else {
            echo "  ✗ $req - MISSING\n";
            $missing[] = $req;
        }
    }
    
    if (empty($missing)) {
        echo "\n✓ All required columns exist!\n";
    } else {
        echo "\n✗ Missing columns: " . implode(', ', $missing) . "\n";
        echo "\nPlease run migrations:\n";
        echo "php artisan migrate\n";
    }
    
    // Check a sample session
    echo "\n\nSample quiz session check:\n";
    $sample = DB::table('quiz_sessions')->first();
    if ($sample) {
        echo "Found " . DB::table('quiz_sessions')->count() . " quiz sessions\n";
        echo "Sample session ID: " . $sample->id . "\n";
        echo "Has assigned_correct_answers: " . (isset($sample->assigned_correct_answers) ? 'YES' : 'NO') . "\n";
        echo "Has shuffled_question_options: " . (isset($sample->shuffled_question_options) ? 'YES' : 'NO') . "\n";
    } else {
        echo "No quiz sessions found in database.\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
