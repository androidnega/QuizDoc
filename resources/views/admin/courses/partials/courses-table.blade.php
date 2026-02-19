<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            @if(!empty($canManageAll) && $canManageAll)
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-10">
                <input type="checkbox" id="select-all-courses" class="h-4 w-4 text-primary-600 border-gray-300 rounded">
            </th>
            @endif
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quizzes</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lecturers</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @foreach($courses as $c)
            <tr class="hover:bg-gray-50">
                @if(!empty($canManageAll) && $canManageAll)
                <td class="px-3 py-2">
                    <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" class="h-4 w-4 text-primary-600 border-gray-300 rounded course-select-checkbox" form="bulk-delete-courses-form">
                </td>
                @endif
                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ $c->code ?? '—' }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $c->name }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600">{{ $c->quizzes_count ?? 0 }}</td>
                <td class="px-3 py-2 text-sm max-w-[220px]">
                    @if($c->examiners->isNotEmpty())
                        <div class="flex flex-wrap gap-1">
                            @foreach($c->examiners as $ex)
                                <span class="inline-flex px-2 py-0.5 text-xs rounded bg-slate-100 text-slate-600">{{ $ex->name ?: $ex->username }}</span>
                            @endforeach
                        </div>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                    @if($c->is_archived)
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Archived</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-success-100 text-success-800">Active</span>
                    @endif
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-right text-sm">
                    @if(isset($canManageAll) && $canManageAll)
                    <a href="{{ route('dashboard.courses.edit', $c) }}" class="text-primary-600 hover:text-primary-900 mr-2">Edit</a>
                    @if($c->is_archived)
                        <form action="{{ route('dashboard.courses.unarchive', $c) }}" method="post" class="inline mr-2">
                            @csrf
                            <button type="submit" class="text-success-600 hover:text-success-800" title="Restore: make this course active again so it appears in dropdowns and can be used for new quizzes">Restore</button>
                        </form>
                    @else
                        <form action="{{ route('dashboard.courses.archive', $c) }}" method="post" class="inline mr-2" onsubmit="return confirm('Archive this course? It will be hidden from active lists but existing quizzes are kept.');">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-800" title="Archive: hide from active lists (e.g. no longer offered). Existing quizzes and data are kept. Use Restore to make active again.">Archive</button>
                        </form>
                    @endif
                    <form action="{{ route('dashboard.courses.destroy', $c) }}" method="post" class="inline" onsubmit="return confirm('Permanently delete course \'{{ addslashes($c->name) }}\'? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-danger-600 hover:text-danger-800" title="Delete course">Delete</button>
                    </form>
                    @else
                    <span class="text-gray-400 text-xs">View only</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
