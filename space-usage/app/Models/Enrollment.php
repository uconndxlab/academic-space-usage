<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'day10_enroll',
        'wsch_max',
        'enrl_cap',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
