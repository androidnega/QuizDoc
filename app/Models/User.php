<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Roles: super_admin (Admin), examiner (Lecturer/Examiner). Students use index only (no account).
     * 
     * Role Responsibilities:
     * - Admin (super_admin): Creates Examiners. Does NOT create courses, classes, or assign examiners.
     * - Coordinator: Creates academic structure (years, levels, courses, classes), assigns Examiners to courses/class groups, uploads students.
     * - Examiner: Views assigned class groups and courses, creates/manages assessments (quizzes).
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_EXAMINER = 'examiner';

    protected $fillable = ['username', 'email', 'phone', 'index_number', 'name', 'course_id', 'role', 'password', 'avatar', 'institution_id', 'sms_allocation', 'ai_quiz_tokens_allocation', 'faculty_id', 'department_id', 'group_leader', 'coordinator'];

    /** Docu Mentor roles */
    public const DM_ROLE_STUDENT = 'student';
    public const DM_ROLE_LEADER = 'leader';
    public const DM_ROLE_SUPERVISOR = 'supervisor';
    public const DM_ROLE_HOD = 'hod';
    public const DM_ROLE_COORDINATOR = 'coordinator';

    protected $hidden = ['password', 'remember_token'];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Passwords are stored hashed (bcrypt) and never in plain text.
     * Applies to both Super Admin and Examiner accounts.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'sms_allocation' => 'integer',
            'sms_used' => 'integer',
            'ai_quiz_tokens_allocation' => 'integer',
            'ai_quiz_tokens_used' => 'integer',
            'ai_quiz_tokens_reset_at' => 'datetime',
            'group_leader' => 'boolean',
            'coordinator' => 'boolean',
        ];
    }

    /** SMS remaining for this examiner (allocation minus used). */
    public function getSmsRemainingAttribute(): int
    {
        $alloc = (int) ($this->attributes['sms_allocation'] ?? 0);
        $used = (int) ($this->attributes['sms_used'] ?? 0);
        return max(0, $alloc - $used);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Courses assigned to this examiner (Coordinator assigns via course_user table). */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_user')->withTimestamps();
    }

    /** Class groups owned by this examiner (examiner_id on class_groups). */
    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class, 'examiner_id');
    }

    /** Class groups where this examiner teaches a course (via class_group_course pivot). */
    public function classGroupsTeaching(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ClassGroup::class, 'class_group_course', 'examiner_id', 'class_group_id');
    }

    /** Docu Mentor: Project groups this user belongs to */
    public function docuMentorGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\DocuMentor\ProjectGroup::class,
            'group_members',
            'user_id',
            'group_id'
        );
    }

    /** Docu Mentor: Groups where this user is leader */
    public function ledDocuMentorGroups(): HasMany
    {
        return $this->hasMany(\App\Models\DocuMentor\ProjectGroup::class, 'leader_id');
    }

    public function isDocuMentorStudent(): bool
    {
        return in_array($this->role, [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER], true);
    }

    /** Examiner = Docu Mentor supervisor (no separate supervisor role). */
    public function isDocuMentorSupervisor(): bool
    {
        return $this->role === self::ROLE_EXAMINER;
    }

    /** Docu Mentor: Projects this user supervises */
    public function supervisedProjects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\DocuMentor\Project::class,
            'project_supervisors',
            'user_id',
            'project_id'
        );
    }

    /** Access to coordinator dashboard: role or coordinator flag. */
    public function isDocuMentorCoordinator(): bool
    {
        return $this->role === self::DM_ROLE_COORDINATOR
            || (bool) ($this->attributes['coordinator'] ?? false)
            || $this->isSuperAdmin();
    }

    /** Can create/manage groups (add first member = auto-create group). Set by coordinator. */
    public function isGroupLeader(): bool
    {
        return (bool) ($this->attributes['group_leader'] ?? false);
    }

    /**
     * Resolve this user's student level value (e.g. 100, 200, 300, 400).
     */
    public function studentLevelValue(): int
    {
        $index = trim((string) ($this->index_number ?? ''));
        if ($index === '') {
            return 0;
        }

        $student = \App\Models\Student::where('index_number_hash', \App\Models\Student::hashIndexNumber($index))->first();
        $levelValue = $student ? (int) ($student->level ?? 0) : 0;
        if ($levelValue <= 0 && $student?->level_id) {
            $lv = \App\Models\StudentLevel::find($student->level_id);
            $levelValue = $lv ? (int) $lv->value : 0;
        }

        if ($levelValue > 0) {
            return $levelValue;
        }

        // Fallback: infer from class group level when student level columns are missing.
        $classGroup = \App\Models\ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($index)])
            ->with('classGroup.level')
            ->first()?->classGroup;
        if ($classGroup && $classGroup->level) {
            return (int) ($classGroup->level->value ?? 0);
        }

        return 0;
    }

    /**
     * Only level 300/400 leaders can create/start Docu Mentor projects.
     */
    public function canLeadDocuMentorProjects(): bool
    {
        $level = $this->studentLevelValue();
        return $this->isGroupLeader() && in_array($level, [300, 400], true);
    }

    /**
     * Student can open project area when level is 300/400 and they are leader or already in a group.
     */
    public function canAccessDocuMentorProjects(): bool
    {
        $level = $this->studentLevelValue();
        if (!in_array($level, [300, 400], true)) {
            return false;
        }
        return $this->canLeadDocuMentorProjects()
            || $this->docuMentorGroups()->exists()
            || $this->ledDocuMentorGroups()->exists();
    }

    /**
     * Class rep = group leader in level 100 or 200 only. Can download class quiz results; not for Docu Mentor.
     * Leaders in level 300+ (Docu Mentor) remain leaders but do not get the rep tag.
     */
    public function isClassRep(): bool
    {
        if (!($this->attributes['group_leader'] ?? false)) {
            return false;
        }
        $index = $this->index_number ?? null;
        if (!$index) {
            return false;
        }
        $student = \App\Models\Student::where('index_number_hash', \App\Models\Student::hashIndexNumber($index))->first();
        $levelValue = $student ? (int) ($student->level ?? 0) : 0;
        if ($levelValue <= 0 && $student?->level_id) {
            $lv = \App\Models\StudentLevel::find($student->level_id);
            $levelValue = $lv ? (int) $lv->value : 0;
        }
        // Rep tag only for level 100 and 200; level 300+ (Docu Mentor leaders) do not get rep tag
        return $levelValue === 100 || $levelValue === 200;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isExaminer(): bool
    {
        return $this->role === self::ROLE_EXAMINER;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_EXAMINER], true);
    }

    /** IDs of courses assigned to this examiner (all courses for Admin/Coordinator; assigned courses for Examiner). */
    public function assignedCourseIds(): array
    {
        if ($this->isSuperAdmin() || $this->isDocuMentorCoordinator()) {
            return Course::where('is_archived', false)->pluck('id')->all();
        }
        return $this->courses()->where('is_archived', false)->pluck('courses.id')->all();
    }

    /**
     * Coordinator (or Super Admin) with SMS balance who "has" this class group.
     * Used for OTP/SMS deduction: all students under class groups deduct from the coordinator's SMS.
     * Coordinator has a class group if: super_admin (all), or coordinator with no department (all), or coordinator's department matches the class group examiner's department.
     */
    public static function coordinatorWithSmsBalanceForClassGroup(ClassGroup $classGroup): ?self
    {
        $classGroup->load('examiner');
        $examinerDepartmentId = $classGroup->examiner?->department_id;

        $q = self::query()
            ->where(function ($q) {
                $q->where('role', self::DM_ROLE_COORDINATOR)
                    ->orWhere('role', self::ROLE_SUPER_ADMIN)
                    ->orWhere('coordinator', true);
            })
            ->whereRaw('(COALESCE(sms_allocation, 0) - COALESCE(sms_used, 0)) > 0');

        $q->where(function ($q) use ($examinerDepartmentId) {
            $q->whereNull('department_id');
            if ($examinerDepartmentId !== null) {
                $q->orWhere('department_id', $examinerDepartmentId);
            }
        });

        return $q->first();
    }

    /** IDs of class groups in scope: all for super_admin; coordinator by department; examiner only groups where they teach a course. */
    public function classGroupIds(): array
    {
        if ($this->isSuperAdmin()) {
            return ClassGroup::pluck('id')->all();
        }
        if ($this->isDocuMentorCoordinator()) {
            $q = ClassGroup::query();
            if ($this->department_id) {
                $q->whereHas('examiner', fn ($e) => $e->where('department_id', $this->department_id));
            }
            return $q->pluck('id')->all();
        }
        // Data isolation: examiners see only groups where they are assigned to teach at least one course
        if (\Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
            return \Illuminate\Support\Facades\DB::table('class_group_course')
                ->where('examiner_id', $this->id)
                ->distinct()
                ->pluck('class_group_id')
                ->all();
        }
        return $this->classGroups()->pluck('id')->all();
    }

    /** Docu Mentor students in scope: same department when coordinator has department; all otherwise. */
    public function docuMentorStudentsInScope(): \Illuminate\Database\Eloquent\Builder
    {
        $q = User::whereIn('role', [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER])->orderBy('name');
        if ($this->department_id) {
            $q->where('department_id', $this->department_id);
        }
        return $q;
    }

    /** Examiners visible to this coordinator (same department). Super Admin or coordinator without department sees all examiners. */
    public function examinersInScope(): \Illuminate\Database\Eloquent\Builder
    {
        $q = User::where('role', self::ROLE_EXAMINER)->orderBy('name');
        if ($this->isSuperAdmin()) {
            return $q;
        }
        if (!$this->department_id) {
            return $q;
        }
        return $q->where('department_id', $this->department_id);
    }

    /** Full URL for avatar (Cloudinary URL or local storage path). */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }
        return asset('storage/' . $this->avatar);
    }

    /**
     * Find or create a Docu Mentor User for a Student (index+phone account).
     * Used when adding a member by phone and the phone is in students.phone_contact but not users.phone.
     */
    public static function findOrCreateDocuMentorUserForStudent(Student $student): ?User
    {
        $indexNormalized = trim($student->index_number ?? '');
        $phone = $student->phone_contact ? preg_replace('/\D/', '', (string) $student->phone_contact) : null;

        $user = null;
        if ($phone && $phone !== '') {
            $user = self::where('phone', $phone)
                ->orWhere('phone', 'like', $phone . '%')
                ->whereIn('role', [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER])
                ->first();
        }
        if (!$user && $indexNormalized !== '') {
            $user = self::where('index_number', $indexNormalized)
                ->whereIn('role', [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER])
                ->first();
        }

        if ($user) {
            return $user;
        }

        if (!$phone || $phone === '') {
            return null;
        }

        $username = 'idx_' . (preg_replace('/[^a-zA-Z0-9]/', '', $indexNormalized) ?: $phone);
        if (self::where('username', $username)->exists()) {
            $username = $username . '_' . substr(md5($indexNormalized . $phone), 0, 6);
        }

        return self::create([
            'username' => $username,
            'index_number' => $indexNormalized ?: null,
            'phone' => $phone,
            'name' => $student->student_name ?? $student->index_number ?? $username,
            'role' => self::DM_ROLE_STUDENT,
            'password' => Hash::make(bin2hex(random_bytes(16))),
        ]);
    }
}
