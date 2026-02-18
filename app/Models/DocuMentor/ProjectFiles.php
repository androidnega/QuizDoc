<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFiles extends Model
{
    protected $table = 'project_files';

    protected $fillable = [
        'brief_pdf', 'diary_pdf', 'assessment_file', 'assessment_form_file',
        'uploaded_at', 'project_id',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
