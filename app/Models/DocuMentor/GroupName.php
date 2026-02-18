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
     * Get two random, distinct group names for a department (or global if department_id null).
     * Always returns two different display names.
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
        $pool = $query->inRandomOrder()->limit(20)->get();
        $seen = [];
        $result = [];
        foreach ($pool as $row) {
            $display = $row->genz_word . ' ' . $row->tech_word;
            if (!isset($seen[$display])) {
                $seen[$display] = true;
                $result[] = $row;
                if (count($result) === 2) {
                    break;
                }
            }
        }
        return $result;
    }
}
