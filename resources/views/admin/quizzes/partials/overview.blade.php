@php
    $approvedCount = $quiz->questions()->count();
    $neededCount = $quiz->getQuestionsPerStudent();
    $shortBy = max(0, $neededCount - $approvedCount);
@endphp
{{-- Action Required: unapproved questions in pool --}}
@if(!$quiz->is_published && !$quiz->hasEnoughApprovedQuestions() && $unapprovedPoolsTotal > 0)
    <div class="bg-primary-50 border-2 border-primary-300 rounded-lg p-4 flex items-start gap-3">
        <svg class="w-6 h-6 text-primary-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
            <p class="font-semibold text-primary-900 mb-1">Action Required: Approve Generated Questions</p>
            <p class="text-sm text-primary-800">
                You have <strong>{{ $unapprovedPoolsTotal }} generated question(s)</strong> waiting for approval below.
                Click <strong>"Approve All ({{ $unapprovedPoolsTotal }})"</strong> to add them to your quiz.
                You need at least <strong>{{ $neededCount }} approved</strong> (currently {{ $approvedCount }}).
            </p>
        </div>
    </div>
@endif
@if(!$quiz->is_published && !$quiz->hasEnoughApprovedQuestions() && $unapprovedPoolsTotal === 0 && $shortBy > 0)
    <div class="bg-warning-50 border-2 border-warning-300 rounded-lg p-4 flex items-start gap-3">
        <svg class="w-6 h-6 text-warning-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
            <p class="font-semibold text-warning-900 mb-1">Need {{ $shortBy }} more question(s) to publish</p>
            <p class="text-sm text-warning-800 mb-3">
                This quiz is set to require <strong>{{ $neededCount }} questions</strong>. You have <strong>{{ $approvedCount }} approved</strong>.
                There are no questions waiting in the pool — the "other {{ $shortBy }}" were never added.
            </p>
            <p class="text-sm text-warning-800 mb-2">You can:</p>
            <ul class="text-sm text-warning-800 list-disc list-inside space-y-1 mb-3">
                <li><strong>Add {{ $shortBy }} more:</strong> Edit the quiz and use AI to generate {{ $shortBy }} more questions (or add manually), then approve them.</li>
                <li><strong>Use {{ $approvedCount }} questions:</strong> Edit the quiz and set "Questions per student" to {{ $approvedCount }} (or less) so you can publish with what you have.</li>
            </ul>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard.quizzes.edit', $quiz) }}" class="btn btn-primary text-sm">Edit quiz (add more or change required number)</a>
            </div>
        </div>
    </div>
@endif

@if($quiz->is_published)
    @php
        $quizWindowOpen = !$quiz->starts_at || $quiz->starts_at->isPast();
        $showEndQuiz = $quizWindowOpen && (!$quiz->ends_at || $quiz->ends_at->isFuture());
        $quizEnded = $quiz->hasEnded();
    @endphp
    <div class="bg-primary-50 border border-primary-200 rounded-lg p-2 min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            @if(!$quizEnded)
            @php $shareUrl = route('student.rules.show.quiz', ['token' => $quiz->link_token]); @endphp
            <span class="text-xs font-medium text-primary-800 shrink-0">Token:</span>
            <input type="text" readonly value="{{ $quiz->link_token }}" id="quiz-token-{{ $quiz->id }}" class="w-36 text-xs font-mono font-semibold text-gray-800 bg-white border border-primary-300 rounded px-1.5 py-0.5" />
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('quiz-token-{{ $quiz->id }}').value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 1500)" class="btn btn-primary text-xs py-0.5 px-2">Copy</button>
            <details class="text-xs ml-1">
                <summary class="cursor-pointer text-primary-600 hover:text-primary-800">link</summary>
                <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                    <input type="text" readonly value="{{ $shareUrl }}" id="quiz-share-url-{{ $quiz->id }}" class="flex-1 min-w-0 max-w-xs text-xs font-mono text-gray-600 bg-white border border-primary-200 rounded px-1.5 py-0.5" />
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('quiz-share-url-{{ $quiz->id }}').value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 1500)" class="btn btn-secondary text-xs py-0.5 px-1.5">Copy</button>
                </div>
            </details>
            <span class="flex-1"></span>
            @endif
            @if($quiz->hasStarted() && !$quiz->hasEnded())
            <div class="flex items-center gap-2">
                <form action="{{ route('dashboard.quizzes.extend-time', $quiz) }}" method="post" class="inline flex items-center gap-1" onsubmit="return confirm('Extend quiz time? This will add time to all active student sessions.');">
                    @csrf
                    <input type="number" name="additional_minutes" min="1" max="120" value="10" required class="w-16 text-xs border border-gray-300 rounded px-1.5 py-0.5" placeholder="min">
                    <button type="submit" class="btn bg-success-100 text-success-700 hover:bg-success-200 text-xs py-0.5 px-2">Extend Time</button>
                </form>
            </div>
            @endif
            @if($showEndQuiz)
            <form action="{{ route('dashboard.quizzes.end', $quiz) }}" method="post" class="inline" onsubmit="return confirm('End this quiz now? Students will no longer be able to start or submit.');">
                @csrf
                <button type="submit" class="btn bg-danger-100 text-danger-700 hover:bg-danger-200 text-xs py-0.5 px-2">End quiz</button>
            </form>
            @elseif($quizEnded)
            <span class="text-xs font-medium text-gray-600 py-0.5 px-2">Quiz ended — token and link are no longer available. You can still view questions, sessions, scores, and analytics below.</span>
            @else
            <form action="{{ route('dashboard.quizzes.unpublish', $quiz) }}" method="post" class="inline">
                @csrf
                <button type="submit" class="btn btn-secondary text-xs py-0.5 px-2">Unpublish</button>
            </form>
            @endif
        </div>
    </div>
@else
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
                <div>
                    <p class="font-medium text-gray-900">This quiz is not published</p>
                    <p class="text-sm text-gray-600">Students cannot see this quiz on the landing page until you publish it.</p>
                    @if(!$quiz->hasEnoughApprovedQuestions())
                        <p class="text-sm text-warning-600 font-medium mt-1">⚠️ Need {{ $quiz->getQuestionsPerStudent() }} approved questions (currently: {{ $quiz->questions->count() }})</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-col items-end gap-2">
                <form action="{{ route('dashboard.quizzes.publish', $quiz) }}" method="post" class="inline">
                    @csrf
                    <button type="submit" class="btn text-sm {{ $quiz->hasEnoughApprovedQuestions() ? 'btn-primary' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}" @if(!$quiz->hasEnoughApprovedQuestions()) disabled onclick="event.preventDefault(); alert('Please approve at least {{ $quiz->getQuestionsPerStudent() }} questions first. Scroll down to see the \'Approve All\' button.');" @endif>Publish Quiz</button>
                </form>
                @if(!$quiz->hasEnoughApprovedQuestions() && $unapprovedPoolsTotal > 0)
                    <p class="text-xs text-gray-600 text-right">👇 Scroll down &amp; click "Approve All ({{ $unapprovedPoolsTotal }})"</p>
                @endif
            </div>
        </div>
    </div>
@endif

@if($quiz->is_active && !$quiz->hasEnoughApprovedQuestions())
    <div class="mb-6 bg-warning-50 border border-warning-200 rounded-lg p-4 flex gap-3">
        <svg class="w-6 h-6 text-warning-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div class="text-sm text-warning-800">
            <p class="font-medium">Quiz locked until approval complete</p>
            <p>Students cannot see or take this quiz until at least {{ $quiz->getQuestionsPerStudent() }} question(s) are approved. Currently: {{ $quiz->questions->count() }} approved.</p>
        </div>
    </div>
@endif

<div class="grid gap-6">
    @if($unapprovedPools->isNotEmpty())
    <section class="card p-6 border-2 border-warning-200 bg-warning-50/30">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-warning-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-warning-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Question Pool (Unapproved)</h2>
                    <p class="text-sm text-gray-600">{{ $unapprovedPoolsTotal }} AI-generated question(s) awaiting approval (showing {{ $unapprovedPools->count() }} on this page)</p>
                </div>
            </div>
            <div class="w-full sm:w-auto sm:min-w-[200px]">
                <label for="pool-search" class="block text-xs font-medium text-gray-600 mb-1">Search pool</label>
                <input type="text" id="pool-search" placeholder="Type to filter questions…" class="input w-full text-sm py-2 px-3" autocomplete="off">
            </div>
            @if(!$quiz->hasStarted())
            <form action="{{ route('dashboard.quizzes.approve-all-pool', $quiz) }}" method="post" class="inline" onsubmit="return confirm('This will approve ALL {{ $unapprovedPoolsTotal }} pending questions. Continue?');">
                @csrf
                <button type="submit" class="btn btn-primary flex items-center gap-2 {{ $unapprovedPoolsTotal > 0 ? 'animate-pulse' : '' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Approve All ({{ $unapprovedPoolsTotal }})</span>
                </button>
            </form>
            @endif
        </div>
        <div class="space-y-4" id="pool-questions-list">
            @foreach($unapprovedPools as $idx => $pool)
                @php
                    $poolOptTexts = is_array($pool->options ?? null) ? implode(' ', array_column($pool->options, 'text')) : '';
                    $poolSearchText = implode(' ', array_filter([$pool->question_text ?? '', $pool->topic ?? '', $poolOptTexts]));
                @endphp
                <div class="border border-warning-200 rounded-lg p-4 bg-white flex flex-wrap items-start justify-between gap-3 pool-question-row" data-search="{{ strtolower(strip_tags($poolSearchText)) }}">
                    <div class="flex-1 min-w-0">
                        @php
                            $poolRawText = trim((string)($pool->question_text ?? ''));
                            if ($poolRawText !== '') {
                                $poolQuestionText = $pool->question_text;
                            } else {
                                $poolCorrect = collect($pool->options ?? [])->firstWhere('key', $pool->correct_answer);
                                $poolCorrectText = $poolCorrect['text'] ?? '';
                                $poolQuestionText = !empty($pool->topic) ? 'Question about ' . $pool->topic : 'Question (text not available)';
                                if ($poolCorrectText !== '') {
                                    $poolQuestionText .= ' — Correct: ' . $poolCorrectText;
                                }
                            }
                        @endphp
                        <p class="text-gray-900 mb-2">{{ $poolQuestionText }}</p>
                        @if($pool->options)
                            <ul class="text-sm text-gray-600 space-y-1 mb-2">
                                @foreach($pool->options as $opt)
                                    <li><span class="font-medium">{{ $opt['key'] ?? '' }}.</span> {{ $opt['text'] ?? '' }} @if(($opt['key'] ?? '') === $pool->correct_answer)<span class="text-success-600 font-medium"> (correct)</span>@endif</li>
                                @endforeach
                            </ul>
                        @endif
                        @if($pool->topic)
                            <span class="inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">{{ $pool->topic }}</span>
                        @endif
                    </div>
                    @if(!$quiz->hasStarted())
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="{{ route('dashboard.quizzes.pool.edit', [$quiz, $pool]) }}" class="btn btn-secondary text-sm">Edit</a>
                        <form action="{{ route('dashboard.quizzes.pool.approve', [$quiz, $pool]) }}" method="post" class="inline">@csrf<button type="submit" class="btn btn-primary text-sm">Approve</button></form>
                        <form action="{{ route('dashboard.quizzes.pool.reject', [$quiz, $pool]) }}" method="post" class="inline" onsubmit="return confirm('Remove this question from the pool?');">@csrf @method('DELETE')<button type="submit" class="btn bg-danger-100 text-danger-700 hover:bg-danger-200 text-sm">Reject</button></form>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
        @if($unapprovedPools->hasPages())
        <div class="mt-6 flex justify-center">{{ $unapprovedPools->appends(['tab' => 'overview'])->links() }}</div>
        @endif
    </section>
    @endif

    <section class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Questions</h2>
                    <p class="text-sm text-gray-600">{{ $approvedQuestionsTotal ?? 0 }} question(s) in quiz (showing {{ $approvedQuestions->count() ?? 0 }} on this page)</p>
                </div>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('dashboard.quizzes.questions.export.txt', $quiz) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-gray-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-lg border-2 border-yellow-600 transition-colors" download>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Download questions TXT
                </a>
            </div>
        </div>
        <div class="mb-4 pb-4 border-b border-gray-200">
            <label for="questions-search" class="block text-sm font-medium text-gray-700 mb-2">Search questions</label>
            <input type="text" id="questions-search" placeholder="Type to filter by question text, topic, type…" class="input w-full max-w-md text-sm py-2 px-3" autocomplete="off">
        </div>
        @if($approvedQuestions->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @if($unapprovedPoolsTotal > 0)
                    <p class="text-gray-700 mb-2">You have <strong>{{ $unapprovedPoolsTotal }}</strong> question(s) in the pool above.</p>
                    <p class="text-sm text-gray-600 mb-4">Click <strong>Approve All</strong> to add them to the quiz.</p>
                @else
                    <p class="text-gray-500 mb-4">No questions added yet</p>
                    <p class="text-sm text-gray-600">Add questions manually or generate them with AI</p>
                @endif
            </div>
        @else
            <div class="space-y-4" id="approved-questions-list">
                @foreach($approvedQuestions as $idx => $q)
                    @php
                        $rawText = trim((string)($q->text ?? ''));
                        $correctOption = $q->options && is_array($q->options) ? collect($q->options)->firstWhere('key', $q->correct_answer) : null;
                        $correctText = $correctOption['text'] ?? '';
                        if ($rawText !== '') {
                            $questionText = $q->text;
                        } else {
                            $questionText = !empty($q->topic) ? 'Question about ' . $q->topic : 'Question (text not available)';
                            if ($correctText !== '') {
                                $questionText .= ' — Correct: ' . $correctText;
                            }
                        }
                        $qSearchText = implode(' ', array_filter([$questionText, $q->topic ?? '', $q->type ?? '', $q->source ?? '']));
                    @endphp
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-300 transition-colors approved-question-row" data-search="{{ strtolower(strip_tags($qSearchText)) }}">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-700 font-semibold text-sm">{{ $idx + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-900 mb-2">{{ $questionText }}</p>
                                @if($q->options && is_array($q->options) && $rawText !== '')
                                    @if($correctText)
                                        <p class="text-gray-700 text-sm mb-3">{{ $correctText }}</p>
                                    @endif
                                @endif
                                <div class="flex items-center gap-3 text-xs flex-wrap">
                                    <span class="inline-flex px-2 py-1 rounded-full bg-gray-100 text-gray-700">{{ ucfirst($q->type) }}</span>
                                    <span class="inline-flex px-2 py-1 rounded-full @if($q->source === 'ai') bg-primary-100 text-primary-700 @else bg-gray-100 text-gray-700 @endif">{{ ucfirst($q->source) }}</span>
                                    @if($q->topic)<span class="text-gray-500">• {{ $q->topic }}</span>@endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 justify-end">
                            @if(!$quiz->hasStarted())
                            <a href="{{ route('dashboard.quizzes.questions.edit', [$quiz, $q]) }}" class="btn btn-secondary text-sm">Edit</a>
                            <form action="{{ route('dashboard.quizzes.questions.destroy', [$quiz, $q]) }}" method="post" class="inline" onsubmit="return confirm('Remove this question from the quiz?');">@csrf @method('DELETE')<button type="submit" class="btn bg-danger-100 text-danger-700 hover:bg-danger-200 text-sm">Delete</button></form>
                            @else
                            <span class="text-xs text-gray-500">Locked</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
