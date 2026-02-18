<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Academic year: submission deadline (default Sept 30 if not set)
        if (Schema::hasTable('academic_years') && !Schema::hasColumn('academic_years', 'submission_deadline')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->date('submission_deadline')->nullable()->after('is_active');
            });
        }

        // Project proposals: coordinator comment
        if (Schema::hasTable('project_proposals') && !Schema::hasColumn('project_proposals', 'coordinator_comment')) {
            Schema::table('project_proposals', function (Blueprint $table) {
                $table->text('coordinator_comment')->nullable()->after('comment');
            });
        }

        // Projects: status (Draft, Submitted, Approved, In Progress, Completed, Graded, Archived)
        if (Schema::hasTable('projects') && !Schema::hasColumn('projects', 'status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('status', 20)->default('draft')->after('approved');
            });
        }

        // 4. SUPERVISOR APPROVAL TABLE: project + supervisor, approved (bool), approved_at (nullable), unique(project, supervisor), CASCADE on delete
        if (!Schema::hasTable('supervisor_project_approvals')) {
            Schema::create('supervisor_project_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // supervisor
                $table->boolean('approved')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->unique(['project_id', 'user_id']);
            });
        }

        // Student scores (document_score + system_score = 100, per supervisor per student)
        if (!Schema::hasTable('project_student_scores')) {
            Schema::create('project_student_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('student_id'); // user
                $table->unsignedBigInteger('supervisor_id');
                $table->unsignedTinyInteger('document_score')->nullable();
                $table->unsignedTinyInteger('system_score')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->unique(['project_id', 'student_id', 'supervisor_id'], 'pss_project_student_supervisor');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_student_scores');
        Schema::dropIfExists('supervisor_project_approvals');
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'status')) {
            Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('status'));
        }
        if (Schema::hasTable('project_proposals') && Schema::hasColumn('project_proposals', 'coordinator_comment')) {
            Schema::table('project_proposals', fn (Blueprint $table) => $table->dropColumn('coordinator_comment'));
        }
        if (Schema::hasTable('academic_years') && Schema::hasColumn('academic_years', 'submission_deadline')) {
            Schema::table('academic_years', fn (Blueprint $table) => $table->dropColumn('submission_deadline'));
        }
    }
};
