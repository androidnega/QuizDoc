@extends('layouts.student-dashboard')

@section('title', 'Documents')
@php $dashboardTitle = 'Documents'; @endphp

@section('dashboard_content')
<div class="max-w-3xl mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card p-5 space-y-4">
        <h1 class="text-lg font-semibold text-slate-900">Upload document</h1>
        <form action="{{ route('dashboard.documents.store') }}" method="post" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 mb-1">
                    Title (optional)
                </label>
                <input type="text"
                       name="title"
                       id="title"
                       value="{{ old('title') }}"
                       class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none"
                       placeholder="e.g. Project proposal, report, etc.">
            </div>

            <div>
                <label for="file" class="block text-sm font-medium text-slate-700 mb-1">
                    File (PDF, DOC, DOCX)
                </label>
                <input type="file"
                       name="file"
                       id="file"
                       accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                       class="block w-full text-sm text-slate-600 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 file:border file:border-slate-200">
                <p class="text-xs text-slate-500 mt-1">
                    Max size: 5MB. Only PDF, DOC, DOCX allowed.
                </p>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                    Upload
                </button>
            </div>
        </form>
    </div>

    <div class="card p-5">
        <h2 class="text-base font-semibold text-slate-900 mb-3">Your documents</h2>
        @if($documents->isEmpty())
            <p class="text-sm text-slate-500">No documents uploaded yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left text-slate-700">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Title</th>
                            <th class="px-3 py-2">Original file</th>
                            <th class="px-3 py-2">Size</th>
                            <th class="px-3 py-2">Uploaded at</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($documents as $doc)
                            <tr>
                                <td class="px-3 py-2">{{ $doc->title ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $doc->original_name }}</td>
                                <td class="px-3 py-2">
                                    @if($doc->size)
                                        {{ number_format($doc->size / 1024, 1) }} KB
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{ $doc->created_at?->format('M j, Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

