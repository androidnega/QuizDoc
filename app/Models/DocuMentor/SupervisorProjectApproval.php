<?php

namespace App\Models\DocuMentor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 4. SUPERVISOR APPROVAL TABLE – multiple supervisors must approve per project.
 * Fields: project_id, supervisor (user_id), approved (bool default false), approved_at (nullable).
 * Unique (project_id, supervisor). CASCADE on delete for project and supervisor.
 */
class SupervisorProjectApproval extends Model
{
    protected $table = 'supervisor_project_approvals';

    protected $fillable = ['project_id', 'user_id', 'approved', 'approved_at'];

    protected $casts = [
        'approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Alias for user() – supervisor who gave approval. */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
