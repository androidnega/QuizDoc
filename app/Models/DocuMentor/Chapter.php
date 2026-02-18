<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Docu Mentor chapter. Table: chapters (id, project_id, title, order, is_open, completed, max_score).
 */
class Chapter extends Model
{
    protected $table = 'chapters';

    /**
     * Legacy table does not have created_at/updated_at timestamps.
     */
    public $timestamps = false;

    protected $fillable = ['title', 'order', 'is_open', 'completed', 'max_score', 'project_id'];

    protected $casts = [
        'is_open' => 'boolean',
        'completed' => 'boolean',
        'order' => 'integer',
        'max_score' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
