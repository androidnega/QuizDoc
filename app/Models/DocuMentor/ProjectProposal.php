<?php

namespace App\Models\DocuMentor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Docu Mentor project proposal. Table: project_proposals (id, project_id, file, uploaded_by_id, version_number, comment, coordinator_comment, …).
 */
class ProjectProposal extends Model
{
    protected $table = 'project_proposals';

    /**
     * Legacy table does not have created_at/updated_at columns.
     */
    public $timestamps = false;

    protected $fillable = ['file', 'version_number', 'uploaded_at', 'comment', 'coordinator_comment', 'project_id', 'uploaded_by_id'];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'version_number' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
