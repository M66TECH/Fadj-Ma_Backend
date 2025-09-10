<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicament extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'description',
        'dosage',
        'prix',
        'stock',
        'groupe_id',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'prix' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function groupe()
    {
        return $this->belongsTo(GroupeMedicament::class, 'groupe_id');
    }

    public function detailsFactures()
    {
        return $this->hasMany(DetailFacture::class, 'medicament_id');
    }

    public function detailsCommandes()
    {
        return $this->hasMany(DetailCommande::class, 'medicament_id');
    }
}
