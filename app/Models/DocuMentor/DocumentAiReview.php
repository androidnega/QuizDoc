<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAiReview extends Model
{
    protected $table = 'document_ai_reviews';

    protected $fillable = [
        'source_type', 'source_id', 'ai_output', 'created_at',
        'project_id', 'chapter_id', 'submission_id',
    ];

    protected $casts = [
        'ai_output' => 'array',
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
