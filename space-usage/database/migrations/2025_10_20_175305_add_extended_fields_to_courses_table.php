<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Academic organizational data
            $table->string('acad_org_code')->nullable()->after('class_descr');
            $table->string('acad_org')->nullable()->after('acad_org_code');
            $table->string('academic_career_code')->nullable()->after('acad_org');
            $table->string('academic_career')->nullable()->after('academic_career_code');
            $table->string('academic_group_code')->nullable()->after('academic_career');
            $table->string('academic_group')->nullable()->after('academic_group_code');
            
            // Course metadata
            $table->string('course_description')->nullable()->after('class_descr');
            $table->string('course_topic_description')->nullable()->after('course_description');
            $table->string('grading_basis')->nullable()->after('duration_minutes');
            $table->decimal('max_credits', 5, 2)->nullable()->after('grading_basis');
            $table->decimal('min_credits', 5, 2)->nullable()->after('max_credits');
            $table->boolean('variable_credits_flag')->default(false)->after('min_credits');
            
            // Enrollment and fees
            $table->boolean('allow_multi_enroll_flag')->default(false)->after('variable_credits_flag');
            $table->boolean('course_fee_flag')->default(false)->after('allow_multi_enroll_flag');
            $table->decimal('allowable_finaid_credits', 5, 2)->nullable()->after('course_fee_flag');
            $table->integer('course_repeat_limit')->nullable()->after('allowable_finaid_credits');
            
            // Equivalent course tracking
            $table->string('equivalent_course_id')->nullable()->after('course_repeat_limit');
            $table->string('equivalent_course')->nullable()->after('equivalent_course_id');
            
            // IDs for integration
            $table->string('course_id')->nullable()->after('equivalent_course');
            $table->string('course_sid')->nullable()->after('course_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'acad_org_code',
                'acad_org',
                'academic_career_code',
                'academic_career',
                'academic_group_code',
                'academic_group',
                'course_description',
                'course_topic_description',
                'grading_basis',
                'max_credits',
                'min_credits',
                'variable_credits_flag',
                'allow_multi_enroll_flag',
                'course_fee_flag',
                'allowable_finaid_credits',
                'course_repeat_limit',
                'equivalent_course_id',
                'equivalent_course',
                'course_id',
                'course_sid',
            ]);
        });
    }
};
