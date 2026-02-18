<?php

namespace App\Models\DocuMentor;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupName extends Model
{
    protected $table = 'group_names';

    protected $fillable = ['department_id', 'genz_word', 'tech_word'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Display name for the group (e.g. "Chale Compiler") */
    public function getDisplayNameAttribute(): string
    {
        return $this->genz_word . ' ' . $this->tech_word;
    }

    /**
     * Get two random group names for a department (or global if department_id null).
     */
    public static function twoRandomForDepartment(?int $departmentId): array
    {
        $query = static::query();
        if ($departmentId !== null) {
            $query->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)->orWhereNull('department_id');
            });
        } else {
            $query->whereNull('department_id');
        }
        return $query->inRandomOrder()->limit(2)->get()->all();
    }
}
