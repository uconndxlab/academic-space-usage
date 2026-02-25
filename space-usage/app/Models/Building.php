<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Building extends Model
{
    use HasFactory;
    protected $fillable = ['building_code', 'short_building_name', 'description', 'type', 'campus_id'];

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
