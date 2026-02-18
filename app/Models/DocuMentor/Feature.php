<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Docu Mentor project feature. Table: features (id, project_id, name, description).
 */
class Feature extends Model
{
    protected $table = 'features';

    /**
     * Legacy table does not have created_at/updated_at timestamps.
     */
    public $timestamps = false;

    protected $fillable = ['name', 'description', 'project_id'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
