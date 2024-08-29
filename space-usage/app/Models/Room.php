<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['building_id', 'room_descr', 'room_number', 'capacity', 'room_description'];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
