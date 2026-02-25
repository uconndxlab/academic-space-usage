<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    public const ALLOWED_FICM_DESCRIPTIONS = [
        'Classroom',
        'Class Laboratory',
        'Conference Room',
        'Meeting Room',
    ];

    protected $fillable = ['building_id', 'room_descr', 'room_number', 'capacity', 'room_description', 'sa_facility_type', 'dept_name'];

    public static function allowedFicmDescriptions(): array
    {
        return self::ALLOWED_FICM_DESCRIPTIONS;
    }

    public static function isValidFicmDescription(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        return in_array(trim($value), self::ALLOWED_FICM_DESCRIPTIONS, true);
    }

    public function scopeLabRoom($query)
    {
        return $query->where('sa_facility_type', 'like', '%Laboratory%');
    }

    public function scopeNotLabRoom($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('sa_facility_type')
              ->orWhere('sa_facility_type', 'not like', '%Laboratory%');
        });
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
