<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicamentImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicament_id',
        'path',
        'is_primary',
        'ordre',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'ordre' => 'integer',
    ];

    public function medicament()
    {
        return $this->belongsTo(Medicament::class, 'medicament_id');
    }

    /**
     * URL complète de l'image
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }
}