<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $casts = [
        'enrollments_by_dept' => 'array',
    ];

    protected $fillable = ['course_id', 'enrol_cap', 'day10_enrol', 'room_id'];

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
}
