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
     * Excludes display names already used in the given academic year so picks are unique.
     * Uses full pool and PHP shuffle for real variety (not just limit 20).
     *
     * @param  int|null  $departmentId  Department ID or null for global only
     * @param  array<string>  $excludeDisplayNames  Display names already used (e.g. in same academic year)
     * @return array<GroupName>  Up to 2 distinct GroupName models
     */
    public static function twoRandomForDepartment(?int $departmentId, array $excludeDisplayNames = []): array
    {
        $query = static::query();
        if ($departmentId !== null) {
            $query->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)->orWhereNull('department_id');
            });
        } else {
            $query->whereNull('department_id');
        }
        $excludeSet = array_flip(array_map('trim', $excludeDisplayNames));
        $pool = $query->get();
        $byDisplay = [];
        foreach ($pool as $row) {
            $display = trim($row->genz_word . ' ' . $row->tech_word);
            if ($display === '') {
                continue;
            }
            if (isset($excludeSet[$display])) {
                continue;
            }
            $byDisplay[$display] = $row;
        }
        $candidates = array_values($byDisplay);
        if (count($candidates) === 0) {
            return [];
        }
        shuffle($candidates);
        return array_slice($candidates, 0, 2);
    }
}
