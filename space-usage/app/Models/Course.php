<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_code', 
        'class_descr', 
        'catalog_number', 
        'wsch_max', 
        'term_id', 
        'class_duration_weekly', 
        'duration_minutes',
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
    ];

    protected $casts = [
        'variable_credits_flag' => 'boolean',
        'allow_multi_enroll_flag' => 'boolean',
        'course_fee_flag' => 'boolean',
    ];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
