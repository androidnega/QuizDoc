<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamCalendar extends Model
{
    protected $table = 'exam_calendar';

    public const EXAM_TYPE_MIDSEM = 'midsem';
    public const EXAM_TYPE_END_OF_SEMESTER = 'end_of_semester';

    public const MODE_ONLINE = 'online';
    public const MODE_IN_PERSON = 'in_person';

    protected $fillable = [
        'class_group_id',
        'course_id',
        'course_name',
        'exam_type',
        'scheduled_at',
        'ends_at',
        'lecturer',
        'mode',
        'venue',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public static function examTypeOptions(): array
    {
        return [
            self::EXAM_TYPE_MIDSEM => 'Midsem',
            self::EXAM_TYPE_END_OF_SEMESTER => 'End of semester',
        ];
    }

    public static function modeOptions(): array
    {
        return [
            self::MODE_ONLINE => 'Online',
            self::MODE_IN_PERSON => 'In-person',
        ];
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function getCourseDisplayAttribute(): string
    {
        if ($this->course_id && $this->relationLoaded('course') && $this->course) {
            return $this->course->name ?? $this->course->code ?? '—';
        }
        return $this->course_name ?? '—';
    }

    public function getExamTypeLabelAttribute(): string
    {
        return self::examTypeOptions()[$this->exam_type] ?? $this->exam_type;
    }

    public function getModeLabelAttribute(): string
    {
        return self::modeOptions()[$this->mode] ?? $this->mode;
    }
}
