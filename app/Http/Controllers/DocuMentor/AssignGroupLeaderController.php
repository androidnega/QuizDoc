<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AssignGroupLeaderController extends Controller
{
    /**
     * List students (and leaders) with group_leader toggle. Upload form for bulk assign.
     */
    public function index(): View
    {
        $columns = ['id', 'name', 'username', 'index_number', 'phone', 'role'];
        if (Schema::hasColumn('users', 'group_leader')) {
            $columns[] = 'group_leader';
        }
        $users = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
            ->orderBy('name')
            ->get($columns);
        return view('docu-mentor.coordinators.assign-group-leaders.index', compact('users'));
    }

    /**
     * Add a single student by index number and set as group leader.
     */
    public function add(Request $request): RedirectResponse
    {
        if (!Schema::hasColumn('users', 'group_leader')) {
            return back()->with('error', 'Database migration required. Run: php artisan migrate');
        }
        $request->validate(['index_number' => 'required|string|max:64']);
        $indexNumber = trim($request->index_number);
        if ($indexNumber === '') {
            return back()->with('error', 'Index number is required.');
        }

        $user = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
            ->where(function ($q) use ($indexNumber) {
                $q->where('index_number', $indexNumber)
                    ->orWhereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)]);
            })
            ->first();

        if ($user) {
            $user->update(['group_leader' => true]);
            return back()->with('success', ($user->name ?: $user->index_number) . ' set as group leader.');
        }

        $cgStudent = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)])->first();
        $name = $cgStudent?->student_name ?? null;
        $studentRecord = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
        $name = $name ?? $studentRecord?->student_name ?? $indexNumber;

        $username = 'idx_' . preg_replace('/[^a-zA-Z0-9]/', '_', $indexNumber);
        if (User::where('username', $username)->exists()) {
            $username = $username . '_' . substr(uniqid(), -4);
        }

        $user = User::create([
            'username' => $username,
            'index_number' => $indexNumber,
            'name' => $name,
            'role' => User::DM_ROLE_STUDENT,
            'group_leader' => true,
            'password' => Hash::make(Str::random(32)),
        ]);
        return back()->with('success', 'Student added and set as group leader. Username: ' . $username);
    }

    /**
     * Toggle group_leader for one user (coordinator only).
     */
    public function toggle(Request $request, User $user): RedirectResponse
    {
        if (!Schema::hasColumn('users', 'group_leader')) {
            return back()->with('error', 'Database migration required. Run: php artisan migrate');
        }
        if (!in_array($user->role, [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER], true)) {
            return back()->with('error', 'Only students can be assigned as group leaders.');
        }
        if ($user->isDocuMentorCoordinator()) {
            return back()->with('error', 'A coordinator cannot be a group leader. Unassign coordinator role first.');
        }
        $user->update(['group_leader' => !($user->group_leader ?? false)]);
        $label = ($user->group_leader ?? false) ? 'Group leader assigned.' : 'Group leader removed.';
        return back()->with('success', $label);
    }

    /**
     * Bulk assign group leaders from Excel (index_number or phone column).
     */
    public function upload(Request $request): RedirectResponse
    {
        if (!Schema::hasColumn('users', 'group_leader')) {
            return back()->with('error', 'Database migration required. Run: php artisan migrate');
        }
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:2048']);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not read Excel file. Use .xlsx, .xls or .csv with one column: Index Number or Phone.');
        }

        if (empty($rows)) {
            return back()->with('error', 'File is empty.');
        }

        $header = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $colIndex = null;
        $columnType = null;

        foreach ($header as $i => $h) {
            $h = (string) $h;
            if (str_contains($h, 'index') || $h === 'index_number' || $h === 'index number') {
                $colIndex = $i;
                $columnType = 'index_number';
                break;
            }
            if (str_contains($h, 'phone') || $h === 'phone number') {
                $colIndex = $i;
                $columnType = 'phone';
                break;
            }
        }

        if ($colIndex === null) {
            return back()->with('error', 'No "Index Number" or "Phone" column found. Add one column with that header.');
        }

        $values = [];
        foreach (array_slice($rows, 1) as $row) {
            $val = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : '';
            if ($val !== '') {
                $values[] = $val;
            }
        }
        $values = array_unique(array_filter($values));

        $updated = [];
        $notFound = [];

        DB::transaction(function () use ($values, $columnType, &$updated, &$notFound) {
            foreach ($values as $value) {
                $normalized = preg_replace('/\D/', '', $value);
                if ($columnType === 'index_number') {
                    $user = User::where('index_number', $value)
                        ->orWhere('index_number', $normalized)
                        ->whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                        ->first();
                } else {
                    $user = User::whereIn('role', [User::DM_ROLE_STUDENT, User::DM_ROLE_LEADER])
                        ->where(function ($q) use ($value, $normalized) {
                            $q->where('phone', $value)
                                ->orWhere('phone', 'like', '%' . $normalized)
                                ->orWhereRaw("REPLACE(REPLACE(phone, ' ', ''), '-', '') = ?", [preg_replace('/\D/', '', $value)]);
                        })
                        ->first();
                }
                if ($user) {
                    if (!$user->isDocuMentorCoordinator()) {
                        $user->update(['group_leader' => true]);
                        $updated[] = $user->name . ' (' . ($user->index_number ?? $user->phone ?? $user->id) . ')';
                    }
                } else {
                    $notFound[] = $value;
                }
            }
        });

        $msg = count($updated) . ' set as group leader.';
        if (count($notFound) > 0) {
            $msg .= ' Not found: ' . implode(', ', array_slice($notFound, 0, 10));
            if (count($notFound) > 10) {
                $msg .= ' (+' . (count($notFound) - 10) . ' more)';
            }
        }
        return back()->with('success', $msg)->with('upload_updated', $updated)->with('upload_not_found', $notFound);
    }
}
