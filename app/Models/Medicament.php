<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicament extends Model
{
    use HasFactory;

    public $incrementing = false; 
    protected $keyType = 'string'; 
    

    protected $fillable = [
        'nom',
        'description',
        'dosage',
        'prix',
        'stock',
        'groupe_id',
        'image_path',
        // Champs détaillés
        'composition',
        'fabricant',
        'type_consommation',
        'date_expiration',
        'description_detaillee',
        'dosage_posologie',
        'ingredients_actifs',
        'effets_secondaires',
        'forme_pharmaceutique',
    ];
    

    protected $casts = [
        'prix' => 'decimal:2',
        'stock' => 'integer',
        'date_expiration' => 'date',
    ];

    protected $appends = ['image_url'];

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

    public function images()
    {
        return $this->hasMany(MedicamentImage::class, 'medicament_id');
    }

    /**
     * Obtenir l'URL complète de l'image
     */
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return null;
    }

    public function getGalleryUrlsAttribute()
    {
        return $this->images->map(fn($img) => asset('storage/' . $img->path))->values();
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($medicament) {
            $medicament->id = 'D06ID' . now()->format('YmdHis') . rand(100, 999);
        });
    }
}
