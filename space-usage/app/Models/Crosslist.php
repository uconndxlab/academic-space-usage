<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crosslist extends Model
{
    use HasFactory;

    protected $fillable = [
        'crosslist_id',
        'crosslist_descr',
        'crosslist_combination_type',
        'crosslisted_enrollment_cap',
        'crosslisted_enrollment_total',
        'crosslist_dup',
    ];

    protected $casts = [
        'crosslist_dup' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
