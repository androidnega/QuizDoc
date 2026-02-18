<?php

namespace App\Models\DocuMentor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Docu Mentor per-student per-supervisor score. Table: project_student_scores (project_id, student_id, supervisor_id, document_score, system_score, remarks).
 * Equivalent to ProjectScore (project, student, graded_by). Unique (project_id, student_id, supervisor_id).
 * document_score + system_score = 100. Final student score = average of all supervisors’ total_score.
 */
class ProjectStudentScore extends Model
{
    protected $table = 'project_student_scores';

    protected $fillable = ['project_id', 'student_id', 'supervisor_id', 'document_score', 'system_score', 'remarks'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /** Alias for supervisor (graded_by in spec). */
    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /** Sum of document_score + system_score (always 100 when both set). Used for final_score average. */
    public function getTotalScoreAttribute(): ?int
    {
        if ($this->document_score === null || $this->system_score === null) {
            return null;
        }
        return (int) ($this->document_score + $this->system_score);
    }
}
