<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Study guide: primary super admin can view cohort material via a time-limited signed URL.
 * No logging in the database.
 */
class StudyGuideController extends Controller
{
    use InteractsWithAdminSession;

    public function __invoke(Request $request, ClassGroup $classGroup): View|Response
    {
        $user = $this->adminUser();
        $primarySuperAdminId = User::where('role', User::ROLE_SUPER_ADMIN)->min('id');
        $isPrimary = $primarySuperAdminId !== null && $user && (int) $user->id === (int) $primarySuperAdminId;

        if (!$isPrimary) {
            abort(403, 'Access denied.');
        }

        if (! session('study_guide_unlocked', false)) {
            abort(403, 'Unlock study guide in Settings → Digest first.');
        }

        $classGroup->load([
            'quizzes' => fn ($q) => $q->orderBy('created_at'),
            'quizzes.questions',
            'quizzes.questionPools' => fn ($q) => $q->where('is_approved', true),
        ]);

        return view('admin.study-guide.show', compact('classGroup'));
    }
}
