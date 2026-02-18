<?php

namespace App\Models\DocuMentor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Docu Mentor project. Table: projects (group_id, title, description, approved, approved_by_id, approval_date,
 * academic_year_id, category_id, parent_project_id, budget, submission_deadline, status, is_completed, …).
 * Pivot: project_supervisors (project_id, user_id). Scores: studentScores() → ProjectStudentScore (project_student_scores).
 * 8. RELATIONSHIP: User → Group → Project → Proposal, Chapter (→ Submission), Features, AI Reviews, Scores, Supervisor Approvals.
 * 10. STATUS FLOW: Draft → Submitted → Approved → In Progress → Completed → Graded.
 */
class Project extends Model
{
    protected $table = 'projects';

    /** 10. STATUS FLOW: Draft → Submitted → Approved → In Progress → Completed → Graded */
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_GRADED = 'graded';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title', 'description', 'approved', 'status', 'is_completed', 'approval_date', 'github_link', 'budget',
        'project_link', 'final_submission', 'max_chapters', 'submission_deadline',
        'academic_year_id', 'approved_by_id', 'category_id', 'group_id', 'parent_project_id',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'is_completed' => 'boolean',
        'approval_date' => 'datetime',
        'submission_deadline' => 'datetime',
        'budget' => 'decimal:2',
    ];

    public function completedChaptersCount(): int
    {
        return $this->chapters()->where('completed', true)->count();
    }

    public function isFullyCompleted(): bool
    {
        return $this->chapters()->where('completed', false)->doesntExist();
    }

    /** True when every assigned supervisor has approved via SupervisorProjectApproval (approved = true or approved_at set). */
    public function allSupervisorsApproved(): bool
    {
        $supervisorIds = $this->supervisors()->pluck('users.id');
        if ($supervisorIds->isEmpty()) {
            return false;
        }
        $approvedUserIds = $this->supervisorApprovals()
            ->where(function ($q) {
                $q->where('approved', true)->orWhereNotNull('approved_at');
            })
            ->pluck('user_id');
        return $supervisorIds->diff($approvedUserIds)->isEmpty();
    }

    /**
     * Project is ready for grading when: all 6 chapters completed AND all supervisors have approved.
     * Supervisors can assign individual scores ONLY when this is true.
     */
    public function canSupervisorsGrade(): bool
    {
        return $this->isFullyCompleted() && $this->allSupervisorsApproved();
    }

    /**
     * Project Completion Logic: Project is COMPLETE only when all chapters completed AND all supervisors approved.
     * Auto-update: set is_completed = true when both conditions met.
     */
    public function markCompletedIfReady(): void
    {
        if ($this->canSupervisorsGrade() && $this->status !== self::STATUS_COMPLETED && $this->status !== self::STATUS_GRADED) {
            $this->update(['status' => self::STATUS_COMPLETED, 'is_completed' => true]);
        } elseif ($this->canSupervisorsGrade() && !$this->is_completed) {
            $this->update(['is_completed' => true]);
        }
    }

    /**
     * OPTION A: Final score for one student = average of all supervisors' total_score (document + system) for this project.
     * Returns null if no scores exist for this student.
     */
    public function getFinalScoreForStudent(int $studentId): ?float
    {
        $scores = $this->studentScores()->where('student_id', $studentId)->get();
        if ($scores->isEmpty()) {
            return null;
        }
        $sum = $scores->sum(fn (ProjectStudentScore $s) => ($s->document_score ?? 0) + ($s->system_score ?? 0));
        return round($sum / $scores->count(), 2);
    }

    /**
     * OPTION A: Final scores for all students who have at least one supervisor score. Key = student_id, value = averaged total_score.
     */
    public function getFinalScoresByStudent(): \Illuminate\Support\Collection
    {
        $byStudent = $this->studentScores->groupBy('student_id');
        return $byStudent->map(function ($rows) {
            $sum = $rows->sum(fn (ProjectStudentScore $s) => ($s->document_score ?? 0) + ($s->system_score ?? 0));
            return round($sum / $rows->count(), 2);
        });
    }

    const UPDATED_AT = 'updated_at';
    const CREATED_AT = 'created_at';

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProjectGroup::class, 'group_id');
    }

    public function parentProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'parent_project_id');
    }

    public function supervisors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_supervisors', 'project_id', 'user_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(ProjectProposal::class);
    }

    public function projectFiles(): HasMany
    {
        return $this->hasMany(ProjectFiles::class);
    }

    public function supervisorApprovals(): HasMany
    {
        return $this->hasMany(SupervisorProjectApproval::class);
    }

    public function studentScores(): HasMany
    {
        return $this->hasMany(ProjectStudentScore::class);
    }

    /**
     * Delete this project and all related data (chapters, submissions, proposals, files, scores, etc.).
     * Used by coordinators when deleting a project or a group that has a project.
     */
    public function deleteWithRelated(): void
    {
        $this->load(['chapters.submissions', 'proposals', 'projectFiles', 'studentScores', 'supervisorApprovals']);
        foreach ($this->chapters as $chapter) {
            foreach ($chapter->submissions as $sub) {
                if ($sub->file && Storage::disk('public')->exists($sub->file)) {
                    Storage::disk('public')->delete($sub->file);
                }
                $sub->delete();
            }
            $chapter->delete();
        }
        foreach ($this->proposals as $proposal) {
            if ($proposal->file && Storage::disk('public')->exists($proposal->file)) {
                Storage::disk('public')->delete($proposal->file);
            }
            $proposal->delete();
        }
        foreach ($this->projectFiles as $pf) {
            foreach (['file', 'file_2', 'file_3'] as $field) {
                if (!empty($pf->$field) && Storage::disk('public')->exists($pf->$field)) {
                    Storage::disk('public')->delete($pf->$field);
                }
            }
            $pf->delete();
        }
        if ($this->final_submission && Storage::disk('public')->exists($this->final_submission)) {
            Storage::disk('public')->delete($this->final_submission);
        }
        $this->studentScores()->delete();
        $this->supervisorApprovals()->delete();
        $this->supervisors()->detach();
        $this->features()->delete();
        DocumentAiReview::where('project_id', $this->id)->delete();
        $this->delete();
    }
}
