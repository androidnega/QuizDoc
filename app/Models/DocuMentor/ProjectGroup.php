<?php

namespace App\Models\DocuMentor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Docu Mentor group. Table: groups (id, name, leader_id, academic_year_id, token).
 * Pivot group_members (group_id, user_id) for members. Leader and members are User (not Student).
 */
class ProjectGroup extends Model
{
    protected $table = 'groups';

    public $timestamps = false;

    protected $fillable = ['name', 'leader_id', 'academic_year_id', 'token'];

    protected $casts = [
        'academic_year_id' => 'integer',
        'leader_id' => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'group_id');
    }

    public static function generateToken(): string
    {
        do {
            $token = strtoupper(Str::random(8));
        } while (static::where('token', $token)->exists());

        return $token;
    }
}
