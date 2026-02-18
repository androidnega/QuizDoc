<?php

namespace App\Exports;

use App\Models\ClassGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClassGroupStudentsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected ClassGroup $classGroup
    ) {}

    public function headings(): array
    {
        return [
            'No.',
            'Index Number',
            'Student Name',
            'Phone Number',
        ];
    }

    public function collection(): Collection
    {
        $students = $this->classGroup->students()
            ->with('studentAccount')
            ->orderBy('index_number')
            ->get();

        return $students->map(function ($student, $idx) {
            $phone = $student->studentAccount?->phone_contact ?? null;
            $displayName = $student->studentAccount?->student_name ?? $student->student_name ?? '—';
            
            return [
                $idx + 1,
                $student->index_number,
                $displayName,
                $phone ?: '—',
            ];
        });
    }
}
