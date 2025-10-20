<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $casts = [
        'enrollments_by_dept' => 'array',
        'standard_meeting_pattern' => 'boolean',
        'class_sched_print_instr' => 'boolean',
        'duplicate_meeting_flag' => 'boolean',
        'crosslisted_course_flag' => 'boolean',
        'class_start_date' => 'date',
        'class_end_date' => 'date',
    ];

    protected $fillable = [
        'course_id',
        'section_number',
        'component_code',
        'enrol_cap',
        'day10_enrol',
        'room_id',
        'start_time',
        'end_time',
        'days',
        'enrollments_by_dept',
        'campus_id',
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
        'crosslist_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function enrollments()
    {
        return $this->enrollments_by_dept;
    }

    // Optional: Method to get enrollments by a specific department
    public function getEnrollmentByDept($department)
    {
        return $this->enrollments_by_dept[$department] ?? 0;
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function crosslist()
    {
        return $this->belongsTo(Crosslist::class);
    }
}
