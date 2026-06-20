<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'type')) {
                $table->enum('type', ['admin', 'teacher', 'student'])->default('student')->after('email');
            }
        });

        Schema::table('class_student', function (Blueprint $table) {
            if (!Schema::hasColumn('class_student', 'enrolled_at')) {
                $table->timestamp('enrolled_at')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('class_student', 'status')) {
                $table->enum('status', ['enrolled', 'dropped', 'completed'])->default('enrolled')->after('enrolled_at');
            }
        });

        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'head_teacher_id')) {
                $table->foreignId('head_teacher_id')->nullable()->constrained('users')->onDelete('set null')->after('teacher_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'head_teacher_id')) {
                $table->dropForeign(['head_teacher_id']);
                $table->dropColumn('head_teacher_id');
            }
        });

        Schema::table('class_student', function (Blueprint $table) {
            if (Schema::hasColumn('class_student', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('class_student', 'enrolled_at')) {
                $table->dropColumn('enrolled_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
