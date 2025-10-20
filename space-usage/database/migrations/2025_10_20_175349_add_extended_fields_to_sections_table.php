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
        Schema::table('sections', function (Blueprint $table) {
            // Instruction and delivery mode
            $table->string('instruction_mode_code')->nullable()->after('component_code');
            $table->string('instruction_mode')->nullable()->after('instruction_mode_code');
            
            // Class type and location
            $table->string('class_type_code')->nullable()->after('instruction_mode');
            $table->string('class_type_descr')->nullable()->after('class_type_code');
            $table->string('class_location')->nullable()->after('campus_id');
            
            // Meeting pattern details
            $table->string('meeting_pattern_descr')->nullable()->after('days');
            $table->boolean('standard_meeting_pattern')->default(true)->after('meeting_pattern_descr');
            $table->date('class_start_date')->nullable()->after('end_time');
            $table->date('class_end_date')->nullable()->after('class_start_date');
            $table->integer('class_duration')->nullable()->after('class_end_date'); // in minutes for entire course
            
            // Room capacity request
            $table->integer('room_capacity_request')->nullable()->after('enrol_cap');
            
            // Associated class and instructors
            $table->string('associated_class')->nullable()->after('class_duration');
            $table->integer('number_of_pis')->default(0)->after('associated_class');
            $table->integer('number_of_sis')->default(0)->after('number_of_pis');
            $table->integer('number_of_tas')->default(0)->after('number_of_sis');
            
            // Auto-enrollment
            $table->string('autoenroll_section_key')->nullable()->after('number_of_tas');
            $table->string('autoenroll_1')->nullable()->after('autoenroll_section_key');
            $table->string('autoenroll_2')->nullable()->after('autoenroll_1');
            
            // System IDs
            $table->string('class_sid')->nullable()->after('autoenroll_2');
            $table->string('class_session_cd')->nullable()->after('class_sid');
            $table->string('course_offer_sid')->nullable()->after('class_session_cd');
            $table->integer('course_offer_number')->nullable()->after('course_offer_sid');
            $table->integer('class_number')->nullable()->after('course_offer_number');
            $table->string('course_topic_id')->nullable()->after('class_number');
            $table->boolean('class_sched_print_instr')->default(true)->after('course_topic_id');
            $table->string('class_primary_key')->nullable()->after('class_sched_print_instr');
            
            // Crosslist tracking
            $table->boolean('duplicate_meeting_flag')->default(false)->after('class_primary_key');
            $table->boolean('crosslisted_course_flag')->default(false)->after('duplicate_meeting_flag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn([
                'instruction_mode_code',
                'instruction_mode',
                'class_type_code',
                'class_type_descr',
                'class_location',
                'meeting_pattern_descr',
                'standard_meeting_pattern',
                'class_start_date',
                'class_end_date',
                'class_duration',
                'room_capacity_request',
                'associated_class',
                'number_of_pis',
                'number_of_sis',
                'number_of_tas',
                'autoenroll_section_key',
                'autoenroll_1',
                'autoenroll_2',
                'class_sid',
                'class_session_cd',
                'course_offer_sid',
                'course_offer_number',
                'class_number',
                'course_topic_id',
                'class_sched_print_instr',
                'class_primary_key',
                'duplicate_meeting_flag',
                'crosslisted_course_flag',
            ]);
        });
    }
};
