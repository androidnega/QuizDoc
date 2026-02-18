<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dynamic student levels (admin-managed). Students select from this list.
     * allows_docu_mentor: when true, students with this level can access Docu Mentor.
     */
    public function up(): void
    {
        Schema::create('student_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('value')->unique(); // e.g. 100, 200, 300, 400
            $table->string('label', 100); // e.g. "Level 100", "Level 400 (Project)"
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('allows_docu_mentor')->default(false);
            $table->timestamps();
        });

        // Default levels
        $defaults = [
            ['value' => 100, 'label' => 'Level 100', 'sort_order' => 1, 'allows_docu_mentor' => false],
            ['value' => 200, 'label' => 'Level 200', 'sort_order' => 2, 'allows_docu_mentor' => false],
            ['value' => 300, 'label' => 'Level 300', 'sort_order' => 3, 'allows_docu_mentor' => false],
            ['value' => 400, 'label' => 'Level 400 (Project)', 'sort_order' => 4, 'allows_docu_mentor' => true],
        ];
        foreach ($defaults as $d) {
            \DB::table('student_levels')->insert(array_merge($d, ['created_at' => now(), 'updated_at' => now()]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_levels');
    }
};
