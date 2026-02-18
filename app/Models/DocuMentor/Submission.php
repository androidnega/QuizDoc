<?php

namespace App\Models\DocuMentor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\DocuMentor\DocumentAiReview;

/**
 * Docu Mentor chapter submission. Table: submissions (id, chapter_id, uploaded_by_id, file, comment, is_open, …).
 * uploadedBy → User.
 */
class Submission extends Model
{
    protected $table = 'submissions';

    /**
     * Legacy table does not have created_at/updated_at timestamps.
     */
    public $timestamps = false;

    protected $fillable = [
        'file', 'comment', 'score', 'submitted_at', 'is_open',
        'chapter_id', 'uploaded_by_id',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'is_open' => 'boolean',
        'score' => 'integer',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /** AI reviews for this submission (max 2 per submission). */
    public function aiReviews(): HasMany
    {
        return $this->hasMany(DocumentAiReview::class);
    }
}
