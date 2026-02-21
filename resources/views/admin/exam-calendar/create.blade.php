@extends('layouts.dashboard')

@section('title', 'Add Exam to Calendar')
@section('dashboard_heading', 'Add Exam to Calendar')

@push('styles')
<style>
    .exam-calendar-form .form-input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        background-color: #f9fafb;
        padding: 0.625rem 0.75rem;
        color: #111827;
        min-height: 44px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .exam-calendar-form .form-input:focus {
        outline: none;
        border-color: #eab308;
        box-shadow: 0 0 0 2px rgba(234, 179, 8, 0.25);
    }
    .exam-calendar-form select.form-input { appearance: auto; }
</style>
@endpush

@section('dashboard_content')
<div class="w-full">
    @if($errors->any())
        <div class="alert alert-error mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <p class="text-sm text-gray-600 mb-6">Assign a midsem or end-of-semester exam to a class group. Students in that class will see it on their dashboard.</p>

        <form action="{{ route('dashboard.exam-calendar.store') }}" method="post" class="exam-calendar-form space-y-6">
            @csrf

            <div>
                <label for="class_group_id" class="block text-sm font-medium text-gray-700 mb-1.5">Class group *</label>
                <select name="class_group_id" id="class_group_id" required class="form-input">
                    <option value="">— Select class group —</option>
                    @foreach($classGroups as $cg)
                        <option value="{{ $cg->id }}" {{ old('class_group_id') == $cg->id ? 'selected' : '' }}>{{ $cg->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1.5">Course *</label>
                <select name="course_id" id="course_id" required class="form-input">
                    <option value="">— Select course —</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" data-lecturers="{{ $c->examiners->map(fn($e) => $e->name ?: $e->username)->join(', ') }}" {{ old('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                    @endforeach
                </select>
                <p id="course_lecturer_display" class="mt-1.5 text-sm text-gray-600 hidden">Lecturer: <span id="course_lecturer_text"></span></p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="exam_type" class="block text-sm font-medium text-gray-700 mb-1.5">Exam type *</label>
                    <select name="exam_type" id="exam_type" required class="form-input">
                        @foreach($examTypeOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('exam_type', 'midsem') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-1.5">Start date & time *</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}" required class="form-input" step="60">
                </div>
                <div>
                    <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1.5">End date & time (optional)</label>
                    <input type="datetime-local" name="ends_at" id="ends_at" value="{{ old('ends_at') }}" class="form-input" step="60">
                </div>
            </div>

            <div>
                <label for="mode" class="block text-sm font-medium text-gray-700 mb-1.5">Mode *</label>
                <select name="mode" id="mode" required class="form-input">
                    @foreach($modeOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('mode', 'online') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="venue" class="block text-sm font-medium text-gray-700 mb-1.5">Venue / link (optional)</label>
                <input type="text" name="venue" id="venue" value="{{ old('venue') }}" maxlength="255" placeholder="Room 101 or exam link URL" class="form-input">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Save exam</button>
                <a href="{{ route('dashboard.exam-calendar.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function() {
    var sel = document.getElementById('course_id');
    var display = document.getElementById('course_lecturer_display');
    var text = document.getElementById('course_lecturer_text');
    if (!sel || !display || !text) return;
    function update() {
        var opt = sel.options[sel.selectedIndex];
        var lecturers = opt && opt.getAttribute('data-lecturers');
        if (lecturers) {
            text.textContent = lecturers;
            display.classList.remove('hidden');
        } else {
            display.classList.add('hidden');
        }
    }
    sel.addEventListener('change', update);
    update();
})();
</script>
@endpush
@endsection
