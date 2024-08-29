<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['subject_code', 'class_descr', 'catalog_number', 'wsch_max', 'term_id', 'class_duration_weekly', 'duration_minutes'];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
