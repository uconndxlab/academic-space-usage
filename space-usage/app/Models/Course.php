<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Room;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'subject_code',
        'catalog_number',
        'section',
        'class_descr',
        'term_code',
        'term',
        'class_duration_weekly',
        'duration_minutes',
        'division',
        'component_code',
        'class_nbr',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'course_department');
    }
    
}
