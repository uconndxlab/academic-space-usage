<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    protected $fillable = [
        'building',
        'building_code',
        'room_number',
        'room_description',
        'capacity',
    ];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
