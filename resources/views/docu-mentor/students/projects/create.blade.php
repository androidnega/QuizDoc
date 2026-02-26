@extends('layouts.student-dashboard')

@section('title', 'Create Project')
@php $dashboardTitle = 'Create Project'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">Create project</h1>
    <p class="text-sm text-slate-500 mt-1">Complete each step in Docu Mentor, then submit your project for Coordinator review.</p>
    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mt-1" id="create-step-indicator">Step 1 of 3</p>
    <p class="text-xs font-semibold text-slate-800 mt-0.5 min-h-[1.25rem]" id="create-step-next-label" aria-live="polite">Next: Project Details →</p>
    {{-- Visual step tracker: active = amber; completed = check; inactive = grey --}}
    <div class="mt-3 inline-flex flex-wrap items-center gap-2 text-xs font-medium" id="create-step-ovals">
        <div class="flex items-center gap-1" data-step-label-wrap="step-1">
            <span data-step-oval="step-1" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-500 text-black shadow-sm">1</span>
            <span class="hidden sm:inline text-slate-800" data-step-label="step-1">Basic</span>
        </div>
        <div class="flex items-center gap-1" data-step-label-wrap="step-2">
            <span data-step-oval="step-2" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-500 border border-slate-300">2</span>
            <span class="hidden sm:inline text-slate-500" data-step-label="step-2">Details</span>
        </div>
        <div class="flex items-center gap-1" data-step-label-wrap="step-3">
            <span data-step-oval="step-3" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-500 border border-slate-300">3</span>
            <span class="hidden sm:inline text-slate-500" data-step-label="step-3">Finish</span>
        </div>
        <button type="button" id="clear-project-form-btn" class="ml-auto px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-slate-300 text-slate-600 hover:bg-slate-50">
            Start fresh
        </button>
    </div>
</header>

<section class="mb-8">
    @if($errors->has('session'))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
            {{ $errors->first('session') }}
        </div>
    @endif
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <form action="{{ route('dashboard.projects.store') }}" method="post" enctype="multipart/form-data" id="project-create-form" class="space-y-6">
            @csrf

            {{-- Step 1: Basic Details --}}
            <div id="step-1" class="project-step space-y-4">
                <h2 class="text-sm font-medium text-slate-700 border-b border-slate-100 pb-2">Step 1: Basic Details</h2>
                <div>
                    <label for="group_id" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Group *</label>
                    <select name="group_id" id="group_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                        @foreach($groupsWithoutProject as $g)
                            <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>
                                {{ $g->name }} ({{ $g->academicYear?->year ?? '—' }})
                            </option>
                        @endforeach
                    </select>
                    @error('group_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="title" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Title *</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400" placeholder="Project title">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="description" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Description (optional, max 700 characters)</label>
                    <textarea name="description" id="description" rows="4" maxlength="700" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400" placeholder="Detailed explanation">{{ old('description') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Max 700 characters.</p>
                    @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="category_id" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Category (optional)</label>
                    <select name="category_id" id="category_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                        <option value="">— None —</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="parent_project_id" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Optional: Tag previous project (parent)</label>
                    <select name="parent_project_id" id="parent_project_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                        <option value="">— None —</option>
                        @foreach($previousProjects as $pp)
                            <option value="{{ $pp->id }}" {{ old('parent_project_id') == $pp->id ? 'selected' : '' }}>{{ $pp->title }} ({{ $pp->academicYear?->year ?? '—' }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">If tagged, you and the supervisor can access the previous project's proposal and Chapter 6 submissions.</p>
                    @error('parent_project_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="button" class="step-next inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-amber-500 text-slate-900 hover:bg-amber-600 min-h-[44px] sm:min-h-0" data-next="step-2">Next: Project Details →</button>
            </div>

            {{-- Step 2: Project Details --}}
            <div id="step-2" class="project-step hidden space-y-4">
                <h2 class="text-sm font-medium text-slate-700 border-b border-slate-100 pb-2">Step 2: Project Details</h2>
                <div>
                    <label for="proposal_file" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Upload proposal (PDF only, max 0.7MB — required)</label>
                    <div class="flex items-center gap-3">
                        <label for="proposal_file" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer"
                               style="background-color: #f59e0b !important; color: #000000 !important;">
                            <i class="fas fa-upload text-xs"></i>
                            <span>Select file</span>
                        </label>
                        <span id="proposal-file-name" class="text-xs text-slate-700 truncate">No file selected</span>
                    </div>
                    <input type="file" name="proposal_file" id="proposal_file" accept=".pdf" class="hidden">
                    <input type="hidden" name="proposal_uploaded_url" id="proposal_uploaded_url" value="">
                    <div class="mt-3 flex items-center gap-2">
                        <div class="flex-1 rounded-full bg-slate-100 overflow-hidden" style="height: 6px;">
                            <div id="proposal-upload-progress"
                                 class="w-0 rounded-full transition-all duration-200 hidden"
                                 style="height: 6px; background-color: #f59e0b !important;"></div>
                        </div>
                        <p id="proposal-upload-label" class="text-xs text-slate-500 whitespace-nowrap">Not uploaded yet</p>
                    </div>
                    @error('proposal_file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-2">Add features</label>
                    <div id="features-container" class="space-y-2">
                        @php $featuresOld = old('features'); @endphp
                        @if(is_array($featuresOld) && count($featuresOld) > 0)
                            @foreach($featuresOld as $idx => $f)
                                <div class="feature-row flex flex-wrap gap-2 items-end">
                                    <input type="text" name="features[{{ $idx }}][name]" value="{{ $f['name'] ?? '' }}" placeholder="Feature name" class="flex-1 min-w-[120px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                                    <input type="text" name="features[{{ $idx }}][description]" value="{{ $f['description'] ?? '' }}" placeholder="Description (optional)" class="flex-1 min-w-[120px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                                    <button type="button" class="remove-feature px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shrink-0 shrink-0">Remove</button>
                                </div>
                            @endforeach
                        @else
                            <div class="feature-row flex flex-wrap gap-2 items-end">
                                <input type="text" name="features[0][name]" value="" placeholder="Feature name" class="flex-1 min-w-[120px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                                <input type="text" name="features[0][description]" value="" placeholder="Description (optional)" class="flex-1 min-w-[120px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                                <button type="button" class="remove-feature px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shrink-0 shrink-0">Remove</button>
                            </div>
                        @endif
                    </div>
                    <button type="button" id="add-feature" class="mt-2 px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shrink-0">+ Add feature</button>
                </div>
                <div>
                    <label for="budget" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Budget (optional)</label>
                    <input type="number" name="budget" id="budget" value="{{ old('budget') }}" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400" placeholder="0.00">
                    @error('budget')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="step-prev px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shrink-0" data-prev="step-1">← Back</button>
                    <button type="button" class="step-next inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-amber-500 text-slate-900 hover:bg-amber-600 min-h-[44px] sm:min-h-0" data-next="step-3">Next: Finish →</button>
                </div>
            </div>

            {{-- Step 3: Finish --}}
            <div id="step-3" class="project-step hidden space-y-4">
                <h2 class="text-sm font-medium text-slate-700 border-b border-slate-100 pb-2">Step 3: Finish</h2>
                <p class="text-sm text-slate-500">Your project will be set to <strong>Pending</strong>. The Coordinator will review it in Docu Mentor and assign a supervisor.</p>
                <div id="step-3-summary" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 hidden" role="status" aria-live="polite">
                    <p class="font-medium text-slate-800">Summary</p>
                    <p class="mt-1"><strong id="step-3-title">—</strong></p>
                    <p class="text-slate-600">Group: <span id="step-3-group">—</span></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="step-prev px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shrink-0" data-prev="step-2">← Back</button>
                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium min-h-[44px] sm:min-h-0 bg-amber-500 text-black hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2">
                        Submit project
                    </button>
                    <a href="{{ route('dashboard.projects.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shrink-0 inline-block">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
(function() {
    var steps = document.querySelectorAll('.project-step');
    var form = document.getElementById('project-create-form');
    var stepIndicator = document.getElementById('create-step-indicator');
    var stepOrder = ['step-1', 'step-2', 'step-3'];
    var stepOvals = document.querySelectorAll('[data-step-oval]');
    var fileInput = document.getElementById('proposal_file');
    var fileNameEl = document.getElementById('proposal-file-name');
    var progressBar = document.getElementById('proposal-upload-progress');
    var progressLabel = document.getElementById('proposal-upload-label');
    var uploadedUrlInput = document.getElementById('proposal_uploaded_url');
    var uploadEndpoint = "{{ route('docu-mentor.students.projects.proposals.upload-temp') }}";
    var csrfRefreshUrl = "{{ route('dashboard.csrf-refresh') }}";
    var STORAGE_KEY_STEP = 'dm_project_create_step';
    var STORAGE_KEY_FORM = 'dm_project_create_form';

    var nextLabelEl = document.getElementById('create-step-next-label');
    var stepLabelEls = document.querySelectorAll('[data-step-label]');
    var step3Summary = document.getElementById('step-3-summary');
    var step3TitleEl = document.getElementById('step-3-title');
    var step3GroupEl = document.getElementById('step-3-group');

    function updateStepOvals(activeStepId) {
        if (!stepOvals.length) return;
        var idx = stepOrder.indexOf(activeStepId);
        stepOvals.forEach(function(oval) {
            var stepId = oval.getAttribute('data-step-oval');
            var stepIdx = stepOrder.indexOf(stepId);
            var isActive = stepIdx === idx;
            var isCompleted = stepIdx < idx;
            if (isActive) {
                oval.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-500 text-black shadow-sm';
                oval.textContent = stepIdx + 1;
            } else if (isCompleted) {
                oval.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full bg-green-100 text-green-700 border border-green-300';
                oval.innerHTML = '<i class="fas fa-check text-xs" aria-hidden="true"></i>';
            } else {
                oval.className = 'inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-500 border border-slate-300';
                oval.textContent = stepIdx + 1;
            }
        });
        stepLabelEls.forEach(function(lbl) {
            var stepId = lbl.getAttribute('data-step-label');
            var stepIdx = stepOrder.indexOf(stepId);
            lbl.classList.toggle('text-slate-800', stepIdx === idx);
            lbl.classList.toggle('text-slate-500', stepIdx !== idx);
        });
        if (nextLabelEl) {
            if (idx === 0) nextLabelEl.textContent = 'Next: Project Details →';
            else if (idx === 1) nextLabelEl.textContent = 'Next: Finish →';
            else nextLabelEl.textContent = 'Review and submit below.';
        }
    }

    function persistStep(stepId) {
        try {
            window.localStorage && localStorage.setItem(STORAGE_KEY_STEP, stepId);
        } catch (e) {}
    }

    function saveFormState() {
        if (!form) return;
        var data = {};
        var elements = form.querySelectorAll('input, select, textarea');
        elements.forEach(function(el) {
            if (!el.name) return;
            if (el.type === 'file') return;
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            data[el.name] = el.value;
        });
        try {
            window.localStorage && localStorage.setItem(STORAGE_KEY_FORM, JSON.stringify(data));
        } catch (e) {}
    }

    function restoreFormState() {
        if (!form) return;
        var raw;
        try {
            raw = window.localStorage && localStorage.getItem(STORAGE_KEY_FORM);
        } catch (e) {
            raw = null;
        }
        if (!raw) return;
        var data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            return;
        }
        Object.keys(data || {}).forEach(function(name) {
            var els = form.querySelectorAll('[name=\"' + name.replace(/\"/g, '\\\"') + '\"]');
            if (!els.length) return;
            els.forEach(function(el) {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = el.value === data[name];
                } else {
                    el.value = data[name];
                }
            });
        });
    }

    function applyUploadStateFromHidden() {
        if (!progressLabel || !progressBar || !uploadedUrlInput) return;
        if (uploadedUrlInput.value) {
            // Uploaded (success): full-width green bar
            progressBar.classList.remove('hidden');
            progressBar.style.width = '100%';
            progressBar.style.backgroundColor = '#16a34a';
            progressLabel.textContent = 'Upload complete';
            progressLabel.classList.remove('text-slate-500', 'text-red-600');
            progressLabel.classList.add('text-green-600');
        } else {
            // Initial / reset state: show thin grey bar with zero progress
            progressBar.classList.remove('hidden');
            progressBar.style.width = '0%';
            progressBar.style.backgroundColor = '#e5e7eb';
            progressLabel.textContent = 'Not uploaded yet';
            progressLabel.classList.remove('text-red-600', 'text-green-600');
            progressLabel.classList.add('text-slate-500');
        }
    }

    function showStep(stepId) {
        steps.forEach(function(s) {
            s.classList.toggle('hidden', s.id !== stepId);
        });
        var idx = stepOrder.indexOf(stepId);
        if (stepIndicator && idx >= 0) stepIndicator.textContent = 'Step ' + (idx + 1) + ' of 3';
        updateStepOvals(stepId);
        persistStep(stepId);
        if (stepId === 'step-3' && step3Summary && step3TitleEl && step3GroupEl) {
            var titleInput = document.getElementById('title');
            var groupSelect = document.getElementById('group_id');
            var title = (titleInput && titleInput.value) ? titleInput.value.trim() : '';
            var groupText = '—';
            if (groupSelect && groupSelect.selectedIndex >= 0 && groupSelect.options[groupSelect.selectedIndex]) {
                groupText = groupSelect.options[groupSelect.selectedIndex].text || groupSelect.value;
            }
            step3TitleEl.textContent = title || '—';
            step3GroupEl.textContent = groupText;
            step3Summary.classList.remove('hidden');
            // Refresh CSRF token before submit to avoid 419 on live (session/cookie issues)
            if (csrfRefreshUrl && form) {
                fetch(csrfRefreshUrl, { redirect: 'manual', credentials: 'same-origin' })
                    .then(function(r) {
                        if (r.status === 302 || r.type === 'opaqueredirect') {
                            window.location.href = '/login';
                            return null;
                        }
                        return r.ok ? r.json() : null;
                    })
                    .then(function(data) {
                        if (data && data.token) {
                            var tokenInput = form.querySelector('input[name="_token"]');
                            if (tokenInput) tokenInput.value = data.token;
                            var meta = document.querySelector('meta[name="csrf-token"]');
                            if (meta) meta.setAttribute('content', data.token);
                        }
                    });
            }
        }
    }

    function clearFormAndStorage() {
        try {
            if (window.localStorage) {
                localStorage.removeItem(STORAGE_KEY_STEP);
                localStorage.removeItem(STORAGE_KEY_FORM);
            }
        } catch (e) {}
        if (!form) return;
        var groupSelect = document.getElementById('group_id');
        if (groupSelect && groupSelect.options.length) groupSelect.selectedIndex = 0;
        var titleEl = document.getElementById('title');
        if (titleEl) titleEl.value = '';
        var descEl = document.getElementById('description');
        if (descEl) descEl.value = '';
        var catSelect = document.getElementById('category_id');
        if (catSelect && catSelect.options.length) catSelect.selectedIndex = 0;
        var parentSelect = document.getElementById('parent_project_id');
        if (parentSelect && parentSelect.options.length) parentSelect.selectedIndex = 0;
        var budgetEl = document.getElementById('budget');
        if (budgetEl) budgetEl.value = '';
        if (uploadedUrlInput) uploadedUrlInput.value = '';
        if (fileInput) fileInput.value = '';
        if (fileNameEl) fileNameEl.textContent = 'No file selected';
        // Reset upload UI to initial grey state
        applyUploadStateFromHidden();
        var featContainer = document.getElementById('features-container');
        if (featContainer) {
            featContainer.innerHTML = '<div class="feature-row flex flex-wrap gap-2 items-end">' +
                '<input type="text" name="features[0][name]" value="" placeholder="Feature name" class="flex-1 min-w-[120px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">' +
                '<input type="text" name="features[0][description]" value="" placeholder="Description (optional)" class="flex-1 min-w-[120px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">' +
                '<button type="button" class="remove-feature px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shrink-0 shrink-0">Remove</button>' +
                '</div>';
        }
        showStep('step-1');
    }

    var clearBtn = document.getElementById('clear-project-form-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (confirm('Clear the form and start a new project? Any unsent data will be removed.')) {
                clearFormAndStorage();
            }
        });
    }

    // Restore saved form + step on load
    restoreFormState();
    (function initStep() {
        var savedStep = null;
        try {
            savedStep = window.localStorage && localStorage.getItem(STORAGE_KEY_STEP);
        } catch (e) {
            savedStep = null;
        }
        if (!savedStep || stepOrder.indexOf(savedStep) === -1) {
            savedStep = 'step-1';
        }
        showStep(savedStep);
    })();

    // Reflect any restored upload state
    applyUploadStateFromHidden();

    if (form) {
        form.addEventListener('input', saveFormState);
        form.addEventListener('change', saveFormState);
    }

    form.addEventListener('click', function(e) {
        var next = e.target.closest('.step-next');
        var prev = e.target.closest('.step-prev');
        if (next) {
            e.preventDefault();
            showStep(next.getAttribute('data-next'));
        }
        if (prev) {
            e.preventDefault();
            showStep(prev.getAttribute('data-prev'));
        }
    });
    var container = document.getElementById('features-container');
    var addBtn = document.getElementById('add-feature');
    if (addBtn && container) {
        addBtn.addEventListener('click', function() {
            var idx = container.querySelectorAll('.feature-row').length;
            var div = document.createElement('div');
            div.className = 'feature-row flex flex-wrap gap-2 items-end';
            div.innerHTML = '<input type="text" name="features[' + idx + '][name]" placeholder="Feature name" class="flex-1 min-w-[120px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">' +
                '<input type="text" name="features[' + idx + '][description]" placeholder="Description (optional)" class="flex-1 min-w-[120px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">' +
                '<button type="button" class="remove-feature px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shrink-0 shrink-0">Remove</button>';
            container.appendChild(div);
            div.querySelector('.remove-feature').addEventListener('click', function() { div.remove(); });
        });
    }
    container && container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-feature')) {
            e.target.closest('.feature-row').remove();
        }
    });

    // File name + progress reset on file select
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files.length > 0) {
                if (fileNameEl) fileNameEl.textContent = fileInput.files[0].name;
            } else if (fileNameEl) {
                fileNameEl.textContent = 'No file selected';
            }

            if (!fileInput.files || fileInput.files.length === 0) {
                // Reset state if no file
                if (uploadedUrlInput) uploadedUrlInput.value = '';
                applyUploadStateFromHidden();
                return;
            }

            if (!(progressBar && progressLabel && window.XMLHttpRequest && uploadEndpoint)) {
                return;
            }

            // Start upload immediately after file is added
            progressBar.classList.remove('hidden');
            progressBar.style.width = '0%';
            // Upload in progress: orange bar
            progressBar.style.backgroundColor = '#f59e0b';
            progressLabel.textContent = 'Upload progress: 0%';
            progressLabel.classList.remove('text-red-600', 'text-green-600');
            progressLabel.classList.add('text-slate-500');

            var xhr = new XMLHttpRequest();
            var data = new FormData();
            data.append('proposal_file', fileInput.files[0]);

            xhr.open('POST', uploadEndpoint, true);

            var tokenMeta = document.querySelector('meta[name=\"csrf-token\"]');
            if (tokenMeta) {
                xhr.setRequestHeader('X-CSRF-TOKEN', tokenMeta.getAttribute('content'));
            }
            xhr.upload.onprogress = function (event) {
                if (!event.lengthComputable) return;
                var percent = Math.round((event.loaded / event.total) * 100);
                progressBar.style.width = percent + '%';
                progressLabel.textContent = 'Upload progress: ' + percent + '%';
            };

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 400) {
                    try {
                        var resp = JSON.parse(xhr.responseText || '{}');
                        if (resp.ok && resp.url && uploadedUrlInput) {
                            uploadedUrlInput.value = resp.url;
                            // Let the shared helper handle success styling (including green bar)
                            applyUploadStateFromHidden();
                            saveFormState();
                            return;
                        }
                    } catch (e) {}
                    progressLabel.textContent = 'Upload failed. Please try again.';
                    progressLabel.classList.remove('text-slate-500', 'text-green-600');
                    progressLabel.classList.add('text-red-600');
                } else {
                    progressLabel.textContent = 'Upload failed. Please try again.';
                    progressLabel.classList.remove('text-slate-500', 'text-green-600');
                    progressLabel.classList.add('text-red-600');
                }
            };

            xhr.onerror = function () {
                progressLabel.textContent = 'Upload failed. Please check your connection.';
                progressLabel.classList.remove('text-slate-500', 'text-green-600');
                progressLabel.classList.add('text-red-600');
            };

            xhr.send(data);
        });
    }
})();
</script>
@endpush
@endsection
