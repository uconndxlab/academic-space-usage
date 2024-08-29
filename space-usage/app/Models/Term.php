<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasFactory;
    
    protected $fillable = ['term_code', 'term_descr'];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
